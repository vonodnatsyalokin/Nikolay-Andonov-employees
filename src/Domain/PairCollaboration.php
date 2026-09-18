<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Everything one pair of employees worked on together, and for how long in total.
 */
final readonly class PairCollaboration
{
    public int $totalDays;

    /**
     * @param list<ProjectCollaboration> $projects
     */
    public function __construct(
        public EmployeePair $pair,
        public array $projects,
    ) {
        $total = 0;

        foreach ($projects as $project) {
            $total += $project->days;
        }

        $this->totalDays = $total;
    }
}
