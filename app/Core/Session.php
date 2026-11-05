<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    /** Start the PHP session with hardened cookie settings. */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        session_name(config('session.name', 'BIRTHDAY_SID'));
        $https = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        self::enforceIdleTimeout();
    }

    /** End the admin part of the session when they have been idle too long. */
    private static function enforceIdleTimeout(): void
    {
        if (!isset($_SESSION['admin_id'])) {
            return;
        }
        $idleMinutes = (int) config('session.idle_minutes', 60);
        $last        = (int) ($_SESSION['last_activity'] ?? time());
        if ($idleMinutes > 0 && (time() - $last) > $idleMinutes * 60) {
            unset($_SESSION['admin_id'], $_SESSION['admin'], $_SESSION['last_activity'], $_SESSION['login_at']);
            Csrf::rotate();
        } else {
            $_SESSION['last_activity'] = time();
        }
    }

    /** Destroy the current session completely. */
    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'lifetime' => time() - 42000,
                'path'     => $p['path'],
                'domain'   => $p['domain'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }
}
