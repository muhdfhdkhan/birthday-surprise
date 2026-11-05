<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Sliding-window rate limiter backed by the rate_limits table.
 * Works per key (e.g. "admin_login:<ip>:<username>").
 */
final class RateLimiter
{
    /** Is the key blocked? Returns ['blocked' => bool, 'retry_in' => seconds]. */
    public static function status(string $key, int $maxAttempts, int $windowMinutes): array
    {
        $seconds = (int) (Database::scalar(
            'SELECT GREATEST(0, TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), expires_at))
             FROM rate_limits
             WHERE rate_key = :key AND attempts >= :max AND expires_at > UTC_TIMESTAMP()
             LIMIT 1',
            ['key' => $key, 'max' => $maxAttempts]
        ) ?? 0);

        return ['blocked' => $seconds > 0, 'retry_in' => $seconds];
    }

    /** Record a failed attempt inside the sliding window. */
    public static function hit(string $key, int $windowMinutes): void
    {
        Database::query(
            'INSERT INTO rate_limits (rate_key, attempts, window_started_at, expires_at)
             VALUES (:key, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP() + INTERVAL :mins MINUTE)
             ON DUPLICATE KEY UPDATE
               attempts          = IF(expires_at > UTC_TIMESTAMP(), attempts + 1, 1),
               window_started_at = IF(expires_at > UTC_TIMESTAMP(), window_started_at, UTC_TIMESTAMP()),
               expires_at        = IF(expires_at > UTC_TIMESTAMP(), expires_at, UTC_TIMESTAMP() + INTERVAL :mins2 MINUTE)',
            ['key' => $key, 'mins' => $windowMinutes, 'mins2' => $windowMinutes]
        );
    }

    /** Clear attempts after a success. */
    public static function reset(string $key): void
    {
        Database::query('DELETE FROM rate_limits WHERE rate_key = :key', ['key' => $key]);
    }

    /** Remove expired rows (called by cron/cleanup.php). */
    public static function prune(): int
    {
        $stmt = Database::query(
            'DELETE FROM rate_limits WHERE expires_at < UTC_TIMESTAMP() - INTERVAL 1 DAY'
        );
        return $stmt->rowCount();
    }
}
