<?php

declare(strict_types=1);

namespace App\Input;

use DateTimeImmutable;

/**
 * Tries a fixed list of date formats, in order, and uses the first one that fits.
 *
 * Some values are ambiguous on their own: "01/02/2020" is both 1 February and
 * 2 January. Such a value is read with whichever format comes first in the
 * list which is why day-first formats are listed before month-first ones
 */
final class PriorityFormatDateParser implements IDateParser
{
    /**
     * @var list<string>
     */
    public const DEFAULT_FORMATS = [
        // Year first: never ambiguous.
        'Y-m-d',
        'Y/m/d',
        'Y.m.d',
        'Ymd',
        // Day first, then month first: the two orders cannot be told apart.
        'd/m/Y',
        'm/d/Y',
        'd-m-Y',
        'm-d-Y',
        'd.m.Y',
        'd/m/y',
        'd-m-y',
        'd.m.y',
        // Written month names.
        'd M Y',
        'd F Y',
        'M d Y',
        'F d Y',
        'M d, Y',
        'F d, Y',
        'd M, Y',
        'd F, Y',
    ];

    /**
     * @var list<string>
     */
    private readonly array $formats;

    /**
     * @param list<string>|null $formats
     */
    public function __construct(?array $formats = null)
    {
        $this->formats = $formats ?? self::DEFAULT_FORMATS;
    }

    public function parse(string $value): DateTimeImmutable
    {
        $value = trim($value);

        if ($value === '') {
            throw UnparsableDateException::for($value);
        }

        foreach ($this->formats as $format) {
            $date = self::parseWithFormat($value, $format);

            if ($date instanceof DateTimeImmutable) {
                return $date;
            }
        }

        return self::parseLoosely($value);
    }

    /**
     * @return list<string>
     */
    public function formats(): array
    {
        return $this->formats;
    }

    /**
     * Strict single-format parse: the value has to match the format completely,
     * and has to be a real calendar date. Returns null when it does not.
     */
    public static function parseWithFormat(string $value, string $format): ?DateTimeImmutable
    {
        // The leading "!" resets the time fields, so only the date part matters.
        $date = DateTimeImmutable::createFromFormat('!' . $format, trim($value));

        if ($date === false) {
            return null;
        }

        $errors = DateTimeImmutable::getLastErrors();

        if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return null;
        }

        // "Y" also swallows short years, which would read "01.11.13" as the year 1.
        // A four digit year format only accepts a four digit year.
        if (str_contains($format, 'Y') && (int) $date->format('Y') < 1000) {
            return null;
        }

        return $date;
    }

    /**
     * Last resort for formats not on the list, e.g. "1st of March 2020".
     */
    private static function parseLoosely(string $value): DateTimeImmutable
    {
        $timestamp = strtotime($value);

        if ($timestamp === false) {
            throw UnparsableDateException::for($value);
        }

        return (new DateTimeImmutable())->setTimestamp($timestamp)->setTime(0, 0);
    }
}
