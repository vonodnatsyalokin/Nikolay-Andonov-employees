<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * One employee's stint on one project.
 */
final readonly class EmploymentRecord
{
    public function __construct(
        public int $employeeId,
        public int $projectId,
        public DateRange $period,
    ) {
    }
}
