<?php

declare(strict_types=1);

namespace App\Input;

use App\Domain\DateRange;
use App\Domain\EmploymentRecord;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Turns one CSV row - EmpID, ProjectID, DateFrom, DateTo - into an employment record.
 */
final class RowParser
{
    private const EXPECTED_COLUMNS = 4;

    /**
     * Values that mean "still working on the project".
     */
    private const OPEN_ENDED = ['', 'null', '-', 'n/a'];

    private readonly DateTimeImmutable $today;

    public function __construct(
        private readonly IDateParser $dateParser,
        ?DateTimeImmutable $today = null,
    ) {
        $this->today = ($today ?? new DateTimeImmutable())->setTime(0, 0);
    }

    /**
     * @param list<string> $values
     *
     * @throws InvalidRowException
     */
    public function parse(array $values): EmploymentRecord
    {
        if (count($values) !== self::EXPECTED_COLUMNS) {
            throw new InvalidRowException(sprintf(
                'Expected %d values (EmpID, ProjectID, DateFrom, DateTo), got %d.',
                self::EXPECTED_COLUMNS,
                count($values),
            ));
        }

        [$employeeId, $projectId, $dateFrom, $dateTo] = $values;

        return new EmploymentRecord(
            $this->id($employeeId, 'EmpID'),
            $this->id($projectId, 'ProjectID'),
            $this->period($dateFrom, $dateTo),
        );
    }

    private function id(string $value, string $column): int
    {
        if (!ctype_digit($value)) {
            throw new InvalidRowException(sprintf('%s "%s" is not a number.', $column, $value));
        }

        return (int) $value;
    }

    private function period(string $dateFrom, string $dateTo): DateRange
    {
        $from = $this->date($dateFrom, 'DateFrom');

        // An open ended stint is still running, so it lasts until today.
        $to = $this->isOpenEnded($dateTo) ? $this->today : $this->date($dateTo, 'DateTo');

        try {
            return new DateRange($from, $to);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidRowException($exception->getMessage(), 0, $exception);
        }
    }

    private function date(string $value, string $column): DateTimeImmutable
    {
        try {
            return $this->dateParser->parse($value);
        } catch (UnparsableDateException $exception) {
            throw new InvalidRowException(
                sprintf('%s: %s', $column, $exception->getMessage()),
                0,
                $exception,
            );
        }
    }

    private function isOpenEnded(string $value): bool
    {
        return in_array(strtolower($value), self::OPEN_ENDED, true);
    }
}
