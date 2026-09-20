<?php

declare(strict_types=1);

namespace App\Input;

use Generator;

/**
 * Reads a CSV file row by row, without loading it all into memory.
 *
 * Takes care of the things real files do: a byte order mark, a header line,
 * empty lines, padded values and a delimiter that is not always a comma.
 */
final class CsvReader implements ICsvReader
{
    private const DELIMITERS = [',', ';', "\t", '|'];

    /**
     * @return Generator<int, list<string>> line number => trimmed values
     *
     * @throws UnreadableFileException
     */
    public function read(string $path): Generator
    {
        if (!is_file($path) || !is_readable($path)) {
            throw UnreadableFileException::for($path);
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw UnreadableFileException::for($path);
        }

        try {
            $delimiter = $this->detectDelimiter($path);
            $lineNumber = 0;
            $isFirstRow = true;

            while (($values = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $lineNumber++;

                $values = $this->clean($values, $isFirstRow);

                if ($values === []) {
                    continue;
                }

                if ($isFirstRow) {
                    $isFirstRow = false;

                    if ($this->looksLikeHeader($values)) {
                        continue;
                    }
                }

                yield $lineNumber => $values;
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * The delimiter used most often on the first non-empty line wins.
     */
    private function detectDelimiter(string $path): string
    {
        $firstLine = '';
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return ',';
        }

        try {
            while (($line = fgets($handle)) !== false) {
                if (trim($line) !== '') {
                    $firstLine = $line;

                    break;
                }
            }
        } finally {
            fclose($handle);
        }

        $best = ',';
        $bestCount = 0;

        foreach (self::DELIMITERS as $delimiter) {
            $count = substr_count($firstLine, $delimiter);

            if ($count > $bestCount) {
                $best = $delimiter;
                $bestCount = $count;
            }
        }

        return $best;
    }

    /**
     * @param list<string|null> $values
     *
     * @return list<string> empty when the line carries no data at all
     */
    private function clean(array $values, bool $isFirstRow): array
    {
        $cleaned = [];

        foreach ($values as $index => $value) {
            $value ??= '';

            if ($isFirstRow && $index === 0) {
                $value = $this->stripByteOrderMark($value);
            }

            $cleaned[] = trim($value);
        }

        foreach ($cleaned as $value) {
            if ($value !== '') {
                return $cleaned;
            }
        }

        return [];
    }

    private function stripByteOrderMark(string $value): string
    {
        return str_starts_with($value, "\xEF\xBB\xBF") ? substr($value, 3) : $value;
    }

    /**
     * Data rows start with a numeric employee id, header rows do not.
     *
     * @param list<string> $values
     */
    private function looksLikeHeader(array $values): bool
    {
        return !ctype_digit($values[0]);
    }
}
