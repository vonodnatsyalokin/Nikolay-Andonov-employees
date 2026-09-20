<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Application\FindLongestPair;
use App\Input\UnreadableFileException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class FindLongestPairTest extends TestCase
{
    private const TODAY = '2020-06-15';

    /**
     * @var list<string>
     */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }

        $this->files = [];
    }

    public function testReadsAFileAndFindsTheLongestServingPair(): void
    {
        $result = $this->service()->inFile(__DIR__ . '/../Fixtures/employees.csv');

        $longest = $result->longest();

        self::assertCount(1, $longest);
        self::assertSame(143, $longest[0]->pair->firstEmployeeId);
        self::assertSame(218, $longest[0]->pair->secondEmployeeId);

        // Project 12: 1 Dec 2013 - 5 Jan 2014 = 36 days. Project 14: 5 - 10 Mar 2020 = 6 days.
        self::assertSame(42, $longest[0]->totalDays);
        self::assertSame(
            [[12, 36], [14, 6]],
            array_map(
                static fn ($project): array => [$project->projectId, $project->days],
                $longest[0]->projects,
            ),
        );
    }

    public function testReportsTheRowsItHadToSkip(): void
    {
        $result = $this->service()->inFile(__DIR__ . '/../Fixtures/employees.csv');

        self::assertSame(7, $result->rowsRead);
        self::assertCount(1, $result->skippedRows);
        self::assertSame(6, $result->skippedRows[0]->lineNumber);
        self::assertStringContainsString('EmpID', $result->skippedRows[0]->reason);
    }

    public function testKeepsEveryTiedPair(): void
    {
        $path = $this->file(
            "100,12,2020-01-01,2020-01-10\n" .
            "200,12,2020-01-01,2020-01-10\n" .
            "300,14,2020-01-01,2020-01-10\n" .
            "400,14,2020-01-01,2020-01-10\n"
        );

        $longest = $this->service()->inFile($path)->longest();

        self::assertCount(2, $longest);
        self::assertSame(10, $longest[0]->totalDays);
        self::assertSame(10, $longest[1]->totalDays);
    }

    public function testFindsNoPairWhenNobodyEverOverlaps(): void
    {
        $path = $this->file(
            "143,10,2009-01-01,2011-04-27\n" .
            "218,10,2012-05-16,2014-01-01\n"
        );

        $result = $this->service()->inFile($path);

        self::assertSame([], $result->collaborations);
        self::assertSame([], $result->longest());
        self::assertSame(2, $result->rowsRead);
    }

    public function testSurvivesAFileWhereEveryRowIsBroken(): void
    {
        $path = $this->file(
            "143\n" .                              // not enough values
            "143,12,not-a-date,NULL\n" .           // unreadable start date
            "143,12,2020-01-10,2020-01-01\n"       // ends before it starts
        );

        $result = $this->service()->inFile($path);

        self::assertSame([], $result->longest());
        self::assertCount(3, $result->skippedRows);
        self::assertSame([1, 2, 3], array_map(
            static fn ($row): int => $row->lineNumber,
            $result->skippedRows,
        ));
    }

    public function testHandlesAnEmptyFile(): void
    {
        $result = $this->service()->inFile($this->file(''));

        self::assertSame(0, $result->rowsRead);
        self::assertSame([], $result->skippedRows);
        self::assertSame([], $result->longest());
    }

    public function testFailsOnAFileItCannotRead(): void
    {
        $this->expectException(UnreadableFileException::class);

        $this->service()->inFile(__DIR__ . '/does-not-exist.csv');
    }

    private function service(): FindLongestPair
    {
        return FindLongestPair::create(new DateTimeImmutable(self::TODAY));
    }

    private function file(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'employees-test-');
        self::assertIsString($path);

        file_put_contents($path, $contents);
        $this->files[] = $path;

        return $path;
    }
}
