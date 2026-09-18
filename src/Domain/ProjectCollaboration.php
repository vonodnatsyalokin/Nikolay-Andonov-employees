<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * How long one pair worked together on one project.
 */
final readonly class ProjectCollaboration
{
    public function __construct(
        public int $projectId,
        public int $days,
    ) {
    }
}
