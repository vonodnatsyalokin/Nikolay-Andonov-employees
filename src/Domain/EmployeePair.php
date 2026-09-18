<?php

declare(strict_types=1);

namespace App\Domain;

use InvalidArgumentException;

/**
 * Two different employees, in a fixed order, so that (143, 218) and (218, 143) are the same pair.
 */
final readonly class EmployeePair
{
    public int $firstEmployeeId;

    public int $secondEmployeeId;

    public function __construct(int $employeeId, int $otherEmployeeId)
    {
        if ($employeeId === $otherEmployeeId) {
            throw new InvalidArgumentException(
                sprintf('Employee %d cannot be paired with themselves.', $employeeId),
            );
        }

        $this->firstEmployeeId = min($employeeId, $otherEmployeeId);
        $this->secondEmployeeId = max($employeeId, $otherEmployeeId);
    }

    /**
     * Stable identity, usable as an array key.
     */
    public function key(): string
    {
        return $this->firstEmployeeId . '-' . $this->secondEmployeeId;
    }
}
