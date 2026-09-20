<?php

declare(strict_types=1);

namespace App\Input;

use Generator;

interface ICsvReader
{
    /**
     * @return Generator<int, list<string>> line number => trimmed values
     *
     * @throws UnreadableFileException
     */
    public function read(string $path): Generator;
}
