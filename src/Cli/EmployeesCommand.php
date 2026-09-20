<?php

declare(strict_types=1);

namespace App\Cli;

use App\Application\AnalysisResult;
use App\Application\FindLongestPair;
use App\Domain\PairCollaboration;
use App\Input\UnreadableFileException;

/**
 * The command line face of the application: a CSV file in, a table out.
 */
final class EmployeesCommand
{
    private const COLUMNS = ['Employee ID #1', 'Employee ID #2', 'Project ID', 'Days worked'];

    /**
     * @param resource $output
     */
    public function __construct(
        private readonly FindLongestPair $service,
        private readonly mixed $output = STDOUT,
    ) {
    }

    /**
     * @param list<string> $arguments everything after the script name
     *
     * @return int exit code
     */
    public function run(array $arguments): int
    {
        if ($arguments === []) {
            $this->write('Usage: php bin/employees.php <path to csv file>');

            return 1;
        }

        try {
            $result = $this->service->inFile($arguments[0]);
        } catch (UnreadableFileException $exception) {
            $this->write('Error: ' . $exception->getMessage());

            return 1;
        }

        $this->report($result);

        return 0;
    }

    private function report(AnalysisResult $result): void
    {
        $longest = $result->longest();

        if ($longest === []) {
            $this->write('No pair of employees has worked together on a common project.');
        }

        foreach ($longest as $collaboration) {
            $this->writePair($collaboration, count($longest) > 1);
        }

        $this->writeSkippedRows($result);
    }

    private function writePair(PairCollaboration $collaboration, bool $isTied): void
    {
        $this->write(sprintf(
            '%s: %d and %d, %d days worked together',
            $isTied ? 'Tied longest pair' : 'Longest working pair',
            $collaboration->pair->firstEmployeeId,
            $collaboration->pair->secondEmployeeId,
            $collaboration->totalDays,
        ));
        $this->write('');

        $rows = [self::COLUMNS];

        foreach ($collaboration->projects as $project) {
            $rows[] = [
                (string) $collaboration->pair->firstEmployeeId,
                (string) $collaboration->pair->secondEmployeeId,
                (string) $project->projectId,
                (string) $project->days,
            ];
        }

        $this->writeTable($rows);
        $this->write('');
    }

    private function writeSkippedRows(AnalysisResult $result): void
    {
        if ($result->skippedRows === []) {
            return;
        }

        $this->write(sprintf(
            'Skipped %d of %d rows:',
            count($result->skippedRows),
            $result->rowsRead,
        ));

        foreach ($result->skippedRows as $row) {
            $this->write(sprintf('  line %d: %s', $row->lineNumber, $row->reason));
        }
    }

    /**
     * @param list<list<string>> $rows the first row is the header
     */
    private function writeTable(array $rows): void
    {
        $widths = [];

        foreach ($rows as $row) {
            foreach ($row as $column => $value) {
                $widths[$column] = max($widths[$column] ?? 0, strlen($value));
            }
        }

        foreach ($rows as $row) {
            $line = '';

            foreach ($row as $column => $value) {
                $line .= str_pad($value, $widths[$column] + 2);
            }

            $this->write(rtrim($line));
        }
    }

    private function write(string $line): void
    {
        fwrite($this->output, $line . PHP_EOL);
    }
}
