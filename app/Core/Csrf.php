<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    /** Current token (created on demand). */
    public static function token(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    /** Hidden input for forms. */
    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(self::token()) . '">';
    }

    /** Constant-time check of a submitted token. */
    public static function check(mixed $token): bool
    {
        return is_string($token)
            && $token !== ''
            && !empty($_SESSION['_csrf_token'])
            && hash_equals($_SESSION['_csrf_token'], $token);
    }

    /** Rotate after privilege changes (login / logout). */
    public static function rotate(): void
    {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
}
