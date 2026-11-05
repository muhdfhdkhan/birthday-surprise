<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

final class View
{
    /**
     * Render a template. Templates resolve in this order:
     *   1. <project root>/<template>.php      (e.g. admin/login.php)
     *   2. <project root>/app/Views/<template>.php
     */
    public static function render(string $template, array $data = [], ?string $layout = null): void
    {
        $content = self::partial($template, $data);
        if ($layout !== null) {
            // The layout's $content is ALWAYS the rendered HTML (a template
            // may legitimately pass its own 'content' data row).
            $data['content'] = $content;
            echo self::partial($layout, $data);
        } else {
            echo $content;
        }
    }

    /** Render a template to a string. */
    public static function partial(string $template, array $data = []): string
    {
        $file = self::resolve($template);
        extract($data, EXTR_OVERWRITE);
        ob_start();
        try {
            require $file;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }

    private static function resolve(string $template): string
    {
        $safe = str_replace(['..', "\0"], '', $template);
        foreach ([BASE_PATH . '/' . $safe . '.php', BASE_PATH . '/app/Views/' . $safe . '.php'] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }
        throw new \RuntimeException("View not found: {$template}");
    }
}
