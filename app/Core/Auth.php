<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    /** Admin id of the current session or null. */
    public static function id(): ?int
    {
        return isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    /** Current admin snapshot (id, username, display_name, role). */
    public static function user(): ?array
    {
        return $_SESSION['admin'] ?? null;
    }

    /** Establish the admin session. */
    public static function login(array $admin): void
    {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin'] = [
            'id'           => (int) $admin['id'],
            'username'     => (string) $admin['username'],
            'display_name' => (string) ($admin['display_name'] ?? 'Admin'),
            'role'         => (string) ($admin['role'] ?? 'admin'),
        ];
        $_SESSION['last_activity'] = time();
        $_SESSION['login_at']      = time();
        Csrf::rotate();
    }

    /** End the admin session but keep a fresh token for the next login. */
    public static function logout(): void
    {
        unset($_SESSION['admin_id'], $_SESSION['admin'], $_SESSION['last_activity'], $_SESSION['login_at']);
        Csrf::rotate();
        session_regenerate_id(true);
    }
}
