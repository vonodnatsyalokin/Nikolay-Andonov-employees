<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Domain\DateRange;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DateRangeTest extends TestCase
{
    public function testCountsBothEndDays(): void
    {
        self::assertSame(1, self::range('2020-01-01', '2020-01-01')->days());
        self::assertSame(31, self::range('2020-01-01', '2020-01-31')->days());
    }

    public function testRejectsEndDateBeforeStartDate(): void
    {
        $this->expectException(InvalidArgumentException::class);

        self::range('2020-01-31', '2020-01-01');
    }

    /**
     * @param array{string, string} $first
     * @param array{string, string} $second
     */
    #[DataProvider('overlapCases')]
    public function testOverlapDays(array $first, array $second, int $expected): void
    {
        $a = self::range(...$first);
        $b = self::range(...$second);

        self::assertSame($expected, $a->overlapDaysWith($b));
        self::assertSame($expected, $b->overlapDaysWith($a), 'Overlap must be symmetric.');
    }

    /**
     * @return iterable<string, array{array{string, string}, array{string, string}, int}>
     */
    public static function overlapCases(): iterable
    {
        yield 'partial overlap' => [
            ['2020-01-01', '2020-01-10'],
            ['2020-01-08', '2020-01-20'],
            3, // 8th, 9th, 10th
        ];

        yield 'one range inside the other' => [
            ['2020-01-01', '2020-12-31'],
            ['2020-03-01', '2020-03-31'],
            31,
        ];

        yield 'identical ranges' => [
            ['2020-01-01', '2020-01-10'],
            ['2020-01-01', '2020-01-10'],
            10,
        ];

        yield 'touching on a single day' => [
            ['2020-01-01', '2020-01-10'],
            ['2020-01-10', '2020-01-20'],
            1,
        ];

        yield 'same single day' => [
            ['2020-01-01', '2020-01-01'],
            ['2020-01-01', '2020-01-01'],
            1,
        ];

        yield 'adjacent but never together' => [
            ['2020-01-01', '2020-01-10'],
            ['2020-01-11', '2020-01-20'],
            0,
        ];

        yield 'far apart' => [
            ['2019-01-01', '2019-06-30'],
            ['2020-01-01', '2020-06-30'],
            0,
        ];
    }

    public function testIgnoresTimeOfDay(): void
    {
        $morning = new DateRange(
            new DateTimeImmutable('2020-01-01 09:30:00'),
            new DateTimeImmutable('2020-01-10 17:00:00'),
        );

        self::assertSame(10, $morning->days());
        self::assertSame('2020-01-01 00:00:00', $morning->from()->format('Y-m-d H:i:s'));
        self::assertSame('2020-01-10 00:00:00', $morning->to()->format('Y-m-d H:i:s'));
    }

    public function testHandlesLeapDaysAndDaylightSavingShifts(): void
    {
        // Leap year, and a range spanning the European DST change on 2020-03-29.
        self::assertSame(29, self::range('2020-02-01', '2020-02-29')->days());
        self::assertSame(3, self::range('2020-03-28', '2020-03-30')->days());
    }

    private static function range(string $from, string $to): DateRange
    {
        return new DateRange(new DateTimeImmutable($from), new DateTimeImmutable($to));
    }
}
