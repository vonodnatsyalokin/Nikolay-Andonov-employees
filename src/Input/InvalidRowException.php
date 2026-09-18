<?php

declare(strict_types=1);

namespace App\Input;

use RuntimeException;

/**
 * A single CSV row that cannot be used. The file as a whole is still readable.
 */
final class InvalidRowException extends RuntimeException
{
}
