<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\CollaborationCalculator;
use App\Domain\EmploymentRecord;
use App\Input\CsvReader;
use App\Input\ICsvReader;
use App\Input\DetectingDateParser;
use App\Input\InvalidRowException;
use App\Input\PriorityFormatDateParser;
use App\Input\RowParser;
use App\Input\UnreadableFileException;
use DateTimeImmutable;
use Generator;

/**
 * The use case itself: read a CSV file and work out which pair of employees
 * worked together the longest.
 *
 * A row that cannot be read is skipped and reported, so one bad line does not
 * cost the whole file. A file that cannot be read at all is an error.
 */
final readonly class FindLongestPair
{
    public function __construct(
        private ICsvReader $reader,
        private RowParser $rowParser,
        private CollaborationCalculator $calculator,
    ) {
    }

    /**
     * Wiring for the entry points that have no dependency injection container.
     */
    public static function create(?DateTimeImmutable $today = null): self
    {
        return new self(
            new CsvReader(),
            new RowParser(new PriorityFormatDateParser(), $today),
            new CollaborationCalculator(),
        );
    }

    /**
     * @throws UnreadableFileException
     */
    public function inFile(string $path): AnalysisResult
    {
        // A first pass over the dates, so an ambiguous value like 01/02/2020 is
        // read the way the rest of the file is written.
        $rowParser = $this->rowParser->withDateParser(
            DetectingDateParser::forValues($this->datesIn($path)),
        );

        /** @var list<EmploymentRecord> $records */
        $records = [];

        /** @var list<SkippedRow> $skipped */
        $skipped = [];

        $rowsRead = 0;

        foreach ($this->reader->read($path) as $lineNumber => $values) {
            $rowsRead++;

            try {
                $records[] = $rowParser->parse($values);
            } catch (InvalidRowException $exception) {
                $skipped[] = new SkippedRow($lineNumber, $exception->getMessage());
            }
        }

        return new AnalysisResult($this->calculator->calculate($records), $skipped, $rowsRead);
    }

    /**
     * Every value in the two date columns, whether or not it is a date.
     *
     * @return Generator<int, string>
     */
    private function datesIn(string $path): Generator
    {
        foreach ($this->reader->read($path) as $values) {
            yield $values[2] ?? '';
            yield $values[3] ?? '';
        }
    }
}
