<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Visitor
{
    /** Pseudonymous cookie id for returning visitors (no fingerprinting). */
    public static function uid(): string
    {
        $key = 'BIRTHDAY_VID';
        if (!empty($_COOKIE[$key]) && preg_match('/^[a-f0-9]{32}$/', $_COOKIE[$key])) {
            return $_COOKIE[$key];
        }
        $uid  = bin2hex(random_bytes(16));
        $https = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
        if (!headers_sent()) {
            setcookie($key, $uid, [
                'expires'  => time() + 86400 * 365,
                'path'     => '/',
                'secure'   => $https,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        return $uid;
    }

    /** Upsert the visitor row and return its db id. */
    public static function upsert(string $uid): int
    {
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $existing = Database::first('SELECT id, visit_count FROM visitors WHERE visitor_uid = :uid LIMIT 1', ['uid' => $uid]);
        if ($existing !== null) {
            Database::query(
                'UPDATE visitors SET last_seen_at = UTC_TIMESTAMP(), visit_count = visit_count + 1, ip_address = :ip WHERE id = :id',
                ['ip' => client_ip(), 'id' => $existing['id']]
            );
            return (int) $existing['id'];
        }

        Database::query(
            'INSERT INTO visitors (visitor_uid, ip_address, browser, device, os, first_seen_at, last_seen_at)
             VALUES (:uid, :ip, :browser, :device, :os, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
            [
                'uid'     => $uid,
                'ip'      => client_ip(),
                'browser' => self::browser($ua),
                'device'  => self::device($ua),
                'os'      => self::os($ua),
            ]
        );
        return (int) Database::scalar('SELECT id FROM visitors WHERE visitor_uid = :uid LIMIT 1', ['uid' => $uid]);
    }

    public static function browser(string $ua): ?string
    {
        foreach (['Edge', 'OPR', 'Firefox', 'Chrome', 'Safari', 'MSIE', 'Trident'] as $needle) {
            if (stripos($ua, $needle) !== false) {
                return str_replace(['OPR', 'MSIE', 'Trident'], ['Opera', 'IE', 'IE'], $needle);
            }
        }
        return substr($ua, 0, 120) ?: null;
    }

    public static function device(string $ua): string
    {
        if (preg_match('/iPad|tablet/i', $ua)) {
            return 'tablet';
        }
        return preg_match('/Mobile|Android|iPhone/i', $ua) ? 'mobile' : 'desktop';
    }

    public static function os(string $ua): ?string
    {
        foreach (['Windows', 'Mac OS', 'Android', 'iPhone', 'iPad', 'Linux'] as $needle) {
            if (stripos($ua, $needle) !== false) {
                return $needle;
            }
        }
        return null;
    }
}
