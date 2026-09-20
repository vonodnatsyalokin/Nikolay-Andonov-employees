<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\PairCollaboration;

/**
 * What one CSV file turned into: every pair that worked together, and the rows
 * that had to be left out.
 */
final readonly class AnalysisResult
{
    /**
     * @param list<PairCollaboration> $collaborations longest total first
     * @param list<SkippedRow>        $skippedRows
     */
    public function __construct(
        public array $collaborations,
        public array $skippedRows,
        public int $rowsRead,
    ) {
    }

    /**
     * The pair that worked together the longest, or several pairs when they tie.
     *
     * @return list<PairCollaboration>
     */
    public function longest(): array
    {
        if ($this->collaborations === []) {
            return [];
        }

        $longest = $this->collaborations[0]->totalDays;

        return array_values(array_filter(
            $this->collaborations,
            static fn (PairCollaboration $collaboration): bool => $collaboration->totalDays === $longest,
        ));
    }
}
