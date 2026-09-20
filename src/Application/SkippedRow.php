<?php

declare(strict_types=1);

namespace App\Application;

/**
 * A row that could not be used, kept so the user can be told about it.
 */
final readonly class SkippedRow
{
    public function __construct(
        public int $lineNumber,
        public string $reason, // An Enum might be a better fit here
    ) {
    }
}
