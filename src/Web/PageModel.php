<?php

declare(strict_types=1);

namespace App\Web;

use App\Application\AnalysisResult;
use App\Domain\PairCollaboration;

/**
 * Everything the page needs, worked out before any HTML is written.
 */
final readonly class PageModel
{
    /**
     * @param list<PairCollaboration> $longest
     */
    private function __construct(
        public ?AnalysisResult $result = null,
        public array $longest = [],
        public ?string $fileName = null,
        public ?string $error = null,
    ) {
    }

    /**
     * The page as it looks before anything has been uploaded.
     */
    public static function empty(): self
    {
        return new self();
    }

    public static function of(AnalysisResult $result, string $fileName): self
    {
        return new self($result, $result->longest(), $fileName);
    }

    public static function failed(string $error): self
    {
        return new self(error: $error);
    }

    public function hasResult(): bool
    {
        return $this->result instanceof AnalysisResult;
    }
}
