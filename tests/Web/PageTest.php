<?php

declare(strict_types=1);

namespace App\Tests\Web;

use PHPUnit\Framework\TestCase;

/**
 * The page is plain PHP and CSS, so the only thing worth checking automatically
 * is that the two stay in step with each other.
 */
final class PageTest extends TestCase
{
    private const PAGE = __DIR__ . '/../../src/Web/templates/index.phtml';

    private const STYLESHEET = __DIR__ . '/../../public/style.css';

    public function testTheStylesheetThePageAsksForExists(): void
    {
        $page = (string) file_get_contents(self::PAGE);

        self::assertStringContainsString('href="style.css"', $page);
        self::assertFileExists(self::STYLESHEET);
    }

    public function testEveryClassOnThePageIsStyled(): void
    {
        $page = (string) file_get_contents(self::PAGE);
        $stylesheet = (string) file_get_contents(self::STYLESHEET);

        preg_match_all('/class="([a-z\- ]+)"/', $page, $matches);

        $classes = array_unique(explode(' ', implode(' ', $matches[1])));

        self::assertNotSame([], $classes);

        foreach ($classes as $class) {
            self::assertStringContainsString('.' . $class, $stylesheet, sprintf(
                'Class "%s" is used on the page but never styled.',
                $class,
            ));
        }
    }
}
