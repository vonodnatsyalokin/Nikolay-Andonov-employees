<?php

declare(strict_types=1);

namespace App\Tests\Input;

use App\Input\DetectingDateParser;
use App\Input\UnparsableDateException;
use PHPUnit\Framework\TestCase;

final class DetectingDateParserTest extends TestCase
{
    public function testOneImpossibleDayMakesTheWholeFileDayFirst(): void
    {
        // 13 cannot be a month, so this file is written day first.
        $parser = DetectingDateParser::forValues(['13/05/2020', '01/02/2020']);

        self::assertSame('2020-02-01', $parser->parse('01/02/2020')->format('Y-m-d'));
    }

    public function testOneImpossibleMonthMakesTheWholeFileMonthFirst(): void
    {
        // The same file written the American way.
        $parser = DetectingDateParser::forValues(['05/13/2020', '01/02/2020']);

        self::assertSame('2020-01-02', $parser->parse('01/02/2020')->format('Y-m-d'));
    }

    public function testTheEvidenceCanSitAnywhereInTheFile(): void
    {
        $values = array_merge(
            array_fill(0, 50, '01/02/2020'),
            ['12/31/2020'], // only the last value gives the order away
        );

        $parser = DetectingDateParser::forValues($values);

        self::assertSame('2020-01-02', $parser->parse('01/02/2020')->format('Y-m-d'));
    }

    public function testFallsBackToDayFirstWhenTheFileSaysNothing(): void
    {
        $parser = DetectingDateParser::forValues(['01/02/2020', '03/04/2020']);

        self::assertSame('2020-02-01', $parser->parse('01/02/2020')->format('Y-m-d'));
    }

    public function testFallsBackToDayFirstWhenThereAreNoDatesAtAll(): void
    {
        $parser = DetectingDateParser::forValues([]);

        self::assertSame('2020-02-01', $parser->parse('01/02/2020')->format('Y-m-d'));
    }

    public function testAFileMayStillMixFormats(): void
    {
        $parser = DetectingDateParser::forValues([
            '2013-11-01',
            '01.12.2013',
            'Jan 01 2014',
            '2014/01/01',
            '05/13/2020',
        ]);

        self::assertSame('2013-11-01', $parser->parse('2013-11-01')->format('Y-m-d'));
        self::assertSame('2013-12-01', $parser->parse('01.12.2013')->format('Y-m-d'));
        self::assertSame('2014-01-01', $parser->parse('Jan 01 2014')->format('Y-m-d'));
        self::assertSame('2014-01-01', $parser->parse('2014/01/01')->format('Y-m-d'));
        self::assertSame('2020-05-13', $parser->parse('05/13/2020')->format('Y-m-d'));
    }

    public function testTheWinningFormatComesFirst(): void
    {
        $parser = DetectingDateParser::forValues(['05/13/2020', '06/14/2020']);

        self::assertSame('m/d/Y', $parser->formats()[0]);
    }

    public function testStillRejectsSomethingThatIsNotADate(): void
    {
        $parser = DetectingDateParser::forValues(['2020-01-01']);

        $this->expectException(UnparsableDateException::class);

        $parser->parse('not a date');
    }

    public function testIgnoresValuesNoFormatUnderstands(): void
    {
        // "NULL" is not a date, it just means the stint is still running.
        $parser = DetectingDateParser::forValues(['NULL', '', '13/05/2020']);

        self::assertSame('2020-02-01', $parser->parse('01/02/2020')->format('Y-m-d'));
    }
}
