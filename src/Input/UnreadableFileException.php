<?php

declare(strict_types=1);

namespace App\Input;

use RuntimeException;

final class UnreadableFileException extends RuntimeException
{
    public static function for(string $path): self
    {
        return new self(sprintf('File "%s" does not exist or cannot be read.', $path));
    }
}
