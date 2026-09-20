<?php

declare(strict_types=1);

namespace App\Input;

use DateTimeImmutable;

/**
 * Reads the dates of a whole file before parsing any of them.
 *
 * A single value like "01/02/2020" cannot be pinned down - it is 1 February or
 * 2 January depending on where the file comes from. The rest of the file
 * usually can pin it down: if "13/05/2020" appears anywhere, the file cannot be
 * month first, because there is no thirteenth month.
 *
 * So every supported format is scored by how many of the file's dates it can
 * read, and the formats are then tried best first. Formats that score equally
 * keep their default order, and a file that mixes formats still works, because
 * the lower scoring formats are still there as fallbacks.
 */
final class DetectingDateParser implements IDateParser
{
    private function __construct(private readonly PriorityFormatDateParser $parser)
    {
    }

    /**
     * @param iterable<string>  $values     every date written anywhere in the file
     * @param list<string>|null $candidates the formats to choose from
     */
    public static function forValues(iterable $values, ?array $candidates = null): self
    {
        $candidates ??= PriorityFormatDateParser::DEFAULT_FORMATS;

        return new self(new PriorityFormatDateParser(
            self::bestFirst($candidates, self::score($candidates, $values)),
        ));
    }

    public function parse(string $value): DateTimeImmutable
    {
        return $this->parser->parse($value);
    }

    /**
     * The formats in the order this file made them, best first.
     *
     * @return list<string>
     */
    public function formats(): array
    {
        return $this->parser->formats();
    }

    /**
     * @param list<string>     $candidates
     * @param iterable<string> $values
     *
     * @return array<string, int> format => how many values it reads
     */
    private static function score(array $candidates, iterable $values): array
    {
        $scores = array_fill_keys($candidates, 0);

        foreach ($values as $value) {
            foreach ($candidates as $format) {
                if (PriorityFormatDateParser::parseWithFormat($value, $format) instanceof DateTimeImmutable) {
                    $scores[$format]++;
                }
            }
        }

        return $scores;
    }

    /**
     * @param list<string>       $candidates
     * @param array<string, int> $scores
     *
     * @return list<string>
     */
    private static function bestFirst(array $candidates, array $scores): array
    {
        $ranks = array_flip($candidates);

        usort($candidates, static function (string $a, string $b) use ($scores, $ranks): int {
            if ($scores[$a] !== $scores[$b]) {
                return $scores[$b] <=> $scores[$a];
            }

            return $ranks[$a] <=> $ranks[$b];
        });

        return $candidates;
    }
}
