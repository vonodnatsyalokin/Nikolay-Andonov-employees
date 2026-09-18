<?php

declare(strict_types=1);

namespace App\Tests\Input;

use App\Input\InvalidRowException;
use App\Input\PriorityFormatDateParser;
use App\Input\RowParser;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RowParserTest extends TestCase
{
    private const TODAY = '2020-06-15';

    public function testParsesAFullRow(): void
    {
        $record = $this->parser()->parse(['143', '12', '2013-11-01', '2014-01-05']);

        self::assertSame(143, $record->employeeId);
        self::assertSame(12, $record->projectId);
        self::assertSame('2013-11-01', $record->period->from->format('Y-m-d'));
        self::assertSame('2014-01-05', $record->period->to->format('Y-m-d'));
    }

    public function testReadsEachColumnWithItsOwnFormat(): void
    {
        $record = $this->parser()->parse(['143', '12', '01.11.2013', 'Jan 05 2014']);

        self::assertSame('2013-11-01', $record->period->from->format('Y-m-d'));
        self::assertSame('2014-01-05', $record->period->to->format('Y-m-d'));
    }

    #[DataProvider('openEndedValues')]
    public function testAnOpenEndedStintLastsUntilToday(string $dateTo): void
    {
        $record = $this->parser()->parse(['143', '12', '2013-11-01', $dateTo]);

        self::assertSame(self::TODAY, $record->period->to->format('Y-m-d'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function openEndedValues(): iterable
    {
        yield 'NULL' => ['NULL'];
        yield 'lowercase null' => ['null'];
        yield 'empty' => [''];
        yield 'dash' => ['-'];
        yield 'n/a' => ['n/a'];
    }

    /**
     * @param list<string> $values
     */
    #[DataProvider('invalidRows')]
    public function testRejectsUnusableRows(array $values, string $expectedMessage): void
    {
        $this->expectException(InvalidRowException::class);
        $this->expectExceptionMessageMatches($expectedMessage);

        $this->parser()->parse($values);
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function invalidRows(): iterable
    {
        yield 'too few values' => [['143', '12', '2013-11-01'], '/got 3/'];
        yield 'too many values' => [['143', '12', '2013-11-01', '2014-01-05', 'extra'], '/got 5/'];
        yield 'employee id is not a number' => [['abc', '12', '2013-11-01', 'NULL'], '/EmpID/'];
        yield 'project id is empty' => [['143', '', '2013-11-01', 'NULL'], '/ProjectID/'];
        yield 'start date is missing' => [['143', '12', '', 'NULL'], '/DateFrom/'];
        yield 'start date is nonsense' => [['143', '12', 'yesterday-ish', 'NULL'], '/DateFrom/'];
        yield 'end date is nonsense' => [['143', '12', '2013-11-01', '2014-13-45'], '/DateTo/'];
        yield 'end date before start date' => [['143', '12', '2014-01-05', '2013-11-01'], '/before/'];
    }

    private function parser(): RowParser
    {
        return new RowParser(new PriorityFormatDateParser(), new DateTimeImmutable(self::TODAY));
    }
}
