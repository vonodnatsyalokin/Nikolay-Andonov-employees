<?php

declare(strict_types=1);

namespace App\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * A closed period of whole days: both the first and the last day count as worked.
 */
final class DateRange
{
    private readonly DateTimeImmutable $from;

    private readonly DateTimeImmutable $to;

    public function __construct(DateTimeImmutable $from, DateTimeImmutable $to)
    {
        $from = $from->setTime(0, 0);
        $to = $to->setTime(0, 0);

        if ($to < $from) {
            throw new InvalidArgumentException(sprintf(
                'End date %s is before start date %s.',
                $to->format('Y-m-d'),
                $from->format('Y-m-d'),
            ));
        }

        $this->from = $from;
        $this->to = $to;
    }

    public function from(): DateTimeImmutable
    {
        return $this->from;
    }

    public function to(): DateTimeImmutable
    {
        return $this->to;
    }

    /**
     * Number of days this range covers, counting both end days.
     */
    public function days(): int
    {
        return (int) $this->from->diff($this->to)->days + 1;
    }

    /**
     * Number of days both ranges cover, counting both end days. 0 when they never meet.
     */
    public function overlapDaysWith(self $other): int
    {
        $start = max($this->from, $other->from);
        $end = min($this->to, $other->to);

        if ($start > $end) {
            return 0;
        }

        return (int) $start->diff($end)->days + 1;
    }
}
