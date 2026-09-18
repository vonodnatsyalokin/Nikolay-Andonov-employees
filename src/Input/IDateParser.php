<?php

declare(strict_types=1);

namespace App\Input;

use DateTimeImmutable;

/**
 * Turns a date as written in a CSV file into a date object.
 */
interface IDateParser
{
    /**
     * @throws UnparsableDateException when the value is not a date in any supported format
     */
    public function parse(string $value): DateTimeImmutable;
}
