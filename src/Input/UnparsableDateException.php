<?php

declare(strict_types=1);

namespace App\Input;

use RuntimeException;

final class UnparsableDateException extends RuntimeException
{
    public static function for(string $value): self
    {
        return new self(sprintf('"%s" is not a date in any supported format.', $value));
    }
}
