<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Verifies the project skeleton itself: the test suite runs and the
 * `App\` namespace is autoloaded from `src/`.
 */
final class SmokeTest extends TestCase
{
    public function testSourceDirectoryIsAutoloaded(): void
    {
        $autoloader = require __DIR__ . '/../vendor/autoload.php';

        $prefixes = $autoloader->getPrefixesPsr4();

        self::assertArrayHasKey('App\\', $prefixes);
        self::assertSame(
            realpath(__DIR__ . '/../src'),
            realpath($prefixes['App\\'][0]),
        );
    }

    public function testUnknownClassIsNotAutoloadable(): void
    {
        self::assertFalse(class_exists('App\\ThisClassDoesNotExist'));
    }
}
