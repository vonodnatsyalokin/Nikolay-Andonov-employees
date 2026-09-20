<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\CollaborationCalculator;
use App\Domain\EmploymentRecord;
use App\Input\CsvReader;
use App\Input\InvalidRowException;
use App\Input\PriorityFormatDateParser;
use App\Input\RowParser;
use App\Input\UnreadableFileException;
use DateTimeImmutable;

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
        private CsvReader $reader,
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
        /** @var list<EmploymentRecord> $records */
        $records = [];

        /** @var list<SkippedRow> $skipped */
        $skipped = [];

        $rowsRead = 0;

        foreach ($this->reader->read($path) as $lineNumber => $values) {
            $rowsRead++;

            try {
                $records[] = $this->rowParser->parse($values);
            } catch (InvalidRowException $exception) {
                $skipped[] = new SkippedRow($lineNumber, $exception->getMessage());
            }
        }

        return new AnalysisResult($this->calculator->calculate($records), $skipped, $rowsRead);
    }
}
