<?php

declare(strict_types=1);

namespace App\Web;

/**
 * Renders a plain PHP template. Nothing reaches the page without going
 * through escape(), which is why the templates only ever call $this->e().
 */
final class View
{
    public function __construct(
        private readonly string $directory = __DIR__ . '/templates',
    ) {
    }

    public function render(string $template, PageModel $page): string
    {
        ob_start();

        require $this->directory . '/' . $template . '.phtml';

        return (string) ob_get_clean();
    }

    public function e(string|int $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
