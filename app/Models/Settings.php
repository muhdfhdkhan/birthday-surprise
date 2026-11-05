<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use Throwable;

final class Settings
{
    /** Every setting as key => value. */
    public static function all(): array
    {
        $out = [];
        try {
            foreach (Database::all('SELECT setting_key, setting_value FROM app_settings') as $row) {
                $out[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
            }
        } catch (Throwable) {
            // The table may not exist yet in young installs — settings stay empty.
        }
        return $out;
    }

    /** One setting value (or $default when unset / storage unavailable). */
    public static function get(string $key, ?string $default = null): ?string
    {
        try {
            $v = Database::scalar(
                'SELECT setting_value FROM app_settings WHERE setting_key = :k LIMIT 1',
                ['k' => $key]
            );
            return $v === null || $v === '' ? $default : (string) $v;
        } catch (Throwable) {
            return $default;
        }
    }

    /** Upsert one setting. */
    public static function set(string $key, string $value): void
    {
        Database::query(
            'INSERT INTO app_settings (setting_key, setting_value) VALUES (:k, :v)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
            ['k' => $key, 'v' => $value]
        );
    }
}
