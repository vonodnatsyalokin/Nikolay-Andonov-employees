<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Domain\CollaborationCalculator;
use App\Domain\DateRange;
use App\Domain\EmploymentRecord;
use App\Domain\PairCollaboration;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CollaborationCalculatorTest extends TestCase
{
    public function testFindsTheOverlapOfTwoEmployeesOnOneProject(): void
    {
        $result = $this->calculate([
            self::record(143, 12, '2013-11-01', '2014-01-05'),
            self::record(218, 12, '2013-12-01', '2014-03-01'),
        ]);

        self::assertCount(1, $result);
        self::assertSame([143, 218], self::pair($result[0]));
        self::assertSame(36, $result[0]->totalDays); // 1 Dec - 5 Jan, both days counted
        self::assertSame([12], self::projectIds($result[0]));
    }

    public function testAddsUpSeveralCommonProjects(): void
    {
        $result = $this->calculate([
            self::record(143, 12, '2020-01-01', '2020-01-10'), // 10 days together
            self::record(218, 12, '2020-01-01', '2020-01-10'),
            self::record(143, 14, '2020-03-01', '2020-03-05'), // 5 days together
            self::record(218, 14, '2020-03-01', '2020-03-31'),
        ]);

        self::assertCount(1, $result);
        self::assertSame(15, $result[0]->totalDays);
        self::assertSame([12, 14], self::projectIds($result[0]));
        self::assertSame([10, 5], array_map(
            static fn ($project): int => $project->days,
            $result[0]->projects,
        ));
    }

    public function testAddsUpSeveralStintsOnTheSameProject(): void
    {
        $result = $this->calculate([
            self::record(143, 12, '2020-01-01', '2020-01-10'),
            self::record(143, 12, '2020-05-01', '2020-05-10'), // rejoined the same project
            self::record(218, 12, '2020-01-01', '2020-12-31'),
        ]);

        self::assertCount(1, $result);
        self::assertSame(20, $result[0]->totalDays);
        self::assertCount(1, $result[0]->projects, 'One project, one row.');
    }

    public function testSortsByTotalDaysAndBreaksTiesByEmployeeId(): void
    {
        $result = $this->calculate([
            // 300 and 400 work together for 10 days.
            self::record(300, 12, '2020-01-01', '2020-01-10'),
            self::record(400, 12, '2020-01-01', '2020-01-10'),
            // 100 and 200 also work together for 10 days, on another project.
            self::record(100, 14, '2020-01-01', '2020-01-10'),
            self::record(200, 14, '2020-01-01', '2020-01-10'),
            // 500 and 600 work together for 20 days.
            self::record(500, 16, '2020-01-01', '2020-01-20'),
            self::record(600, 16, '2020-01-01', '2020-01-20'),
        ]);

        self::assertSame([[500, 600], [100, 200], [300, 400]], array_map(
            self::pair(...),
            $result,
        ));
    }

    public function testCountsEveryPairInALargerGroup(): void
    {
        $result = $this->calculate([
            self::record(1, 12, '2020-01-01', '2020-01-10'),
            self::record(2, 12, '2020-01-01', '2020-01-10'),
            self::record(3, 12, '2020-01-01', '2020-01-10'),
        ]);

        self::assertSame(
            [[1, 2], [1, 3], [2, 3]],
            array_map(self::pair(...), $result),
        );
    }

    public function testIgnoresEmployeesWhoNeverOverlap(): void
    {
        $result = $this->calculate([
            self::record(143, 10, '2009-01-01', '2011-04-27'),
            self::record(218, 10, '2012-05-16', '2014-01-01'),
        ]);

        self::assertSame([], $result);
    }

    public function testIgnoresDifferentProjectsAtTheSameTime(): void
    {
        $result = $this->calculate([
            // Same period, but different projects.
            self::record(143, 12, '2020-01-01', '2020-06-30'),
            self::record(218, 14, '2020-01-01', '2020-06-30'),
        ]);

        self::assertSame([], $result);
    }

    public function testIgnoresAnEmployeeOverlappingWithThemselves(): void
    {
        $result = $this->calculate([
            self::record(143, 12, '2020-01-01', '2020-06-30'),
            self::record(143, 12, '2020-03-01', '2020-04-30'), // overlapping duplicate row
        ]);

        self::assertSame([], $result);
    }

    public function testHandlesNoRecordsAtAll(): void
    {
        self::assertSame([], $this->calculate([]));
    }

    /**
     * @param list<EmploymentRecord> $records
     *
     * @return list<PairCollaboration>
     */
    private function calculate(array $records): array
    {
        return (new CollaborationCalculator())->calculate($records);
    }

    private static function record(int $employeeId, int $projectId, string $from, string $to): EmploymentRecord
    {
        return new EmploymentRecord(
            $employeeId,
            $projectId,
            new DateRange(new DateTimeImmutable($from), new DateTimeImmutable($to)),
        );
    }

    /**
     * @return array{int, int}
     */
    private static function pair(PairCollaboration $collaboration): array
    {
        return [$collaboration->pair->firstEmployeeId, $collaboration->pair->secondEmployeeId];
    }

    /**
     * @return list<int>
     */
    private static function projectIds(PairCollaboration $collaboration): array
    {
        return array_map(static fn ($project): int => $project->projectId, $collaboration->projects);
    }
}
