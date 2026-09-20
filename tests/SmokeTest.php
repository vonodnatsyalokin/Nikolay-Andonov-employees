<?php

declare(strict_types=1);

namespace App\Tests;

use Composer\Autoload\ClassLoader;
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

        if (!$autoloader instanceof ClassLoader) {
            self::fail('Composer did not return its autoloader.');
        }

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
