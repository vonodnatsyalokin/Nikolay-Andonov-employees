<?php

declare(strict_types=1);

namespace App\Tests\Input;

use App\Input\PriorityFormatDateParser;
use App\Input\UnparsableDateException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PriorityFormatDateParserTest extends TestCase
{
    #[DataProvider('supportedFormats')]
    public function testParsesSupportedFormats(string $value, string $expected): void
    {
        $date = (new PriorityFormatDateParser())->parse($value);

        self::assertSame($expected, $date->format('Y-m-d'));
        self::assertSame('00:00:00', $date->format('H:i:s'), 'Time of day must be reset.');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function supportedFormats(): iterable
    {
        yield 'ISO' => ['2013-11-01', '2013-11-01'];
        yield 'ISO with slashes' => ['2013/11/01', '2013-11-01'];
        yield 'ISO with dots' => ['2013.11.01', '2013-11-01'];
        yield 'compact' => ['20131101', '2013-11-01'];
        yield 'day first with slashes' => ['01/11/2013', '2013-11-01'];
        yield 'day first with dashes' => ['01-11-2013', '2013-11-01'];
        yield 'day first with dots' => ['01.11.2013', '2013-11-01'];
        yield 'day first, short year' => ['01.11.13', '2013-11-01'];
        yield 'short month name' => ['01 Nov 2013', '2013-11-01'];
        yield 'full month name' => ['01 November 2013', '2013-11-01'];
        yield 'month name first' => ['Nov 01 2013', '2013-11-01'];
        yield 'month name first with comma' => ['November 01, 2013', '2013-11-01'];
        yield 'surrounding whitespace' => ["  2013-11-01\t", '2013-11-01'];
    }

    public function testDayFirstWinsOverMonthFirstWhenAmbiguous(): void
    {
        // "01/02/2020" is 1 February (day first) or 2 January (month first).
        $date = (new PriorityFormatDateParser())->parse('01/02/2020');

        self::assertSame('2020-02-01', $date->format('Y-m-d'));
    }

    public function testFallsBackToLooseParsingForFormatsNotOnTheList(): void
    {
        $date = (new PriorityFormatDateParser())->parse('2020-03-01T08:30:00+02:00');

        self::assertSame('2020-03-01', $date->format('Y-m-d'));
    }

    #[DataProvider('unparsableValues')]
    public function testRejectsValuesThatAreNotDates(string $value): void
    {
        $this->expectException(UnparsableDateException::class);

        (new PriorityFormatDateParser())->parse($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unparsableValues(): iterable
    {
        yield 'empty' => [''];
        yield 'whitespace only' => ['   '];
        yield 'words' => ['not a date'];
        yield 'impossible day' => ['2013-11-45'];
        yield 'impossible month' => ['2013-13-01'];
        yield 'number only' => ['143'];
        yield 'trailing junk' => ['2013-11-01 oops'];
    }

    public function testAcceptsACustomFormatList(): void
    {
        $parser = new PriorityFormatDateParser(['m/d/Y']);

        self::assertSame(['m/d/Y'], $parser->formats());
        self::assertSame('2020-01-02', $parser->parse('01/02/2020')->format('Y-m-d'));
    }
}
