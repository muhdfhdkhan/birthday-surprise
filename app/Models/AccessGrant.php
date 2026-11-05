<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class AccessGrant
{
    public static function all(): array
    {
        return Database::all(
            'SELECT g.*, p.display_name AS profile_name, r.id AS request_id
             FROM access_grants g
             LEFT JOIN birthday_profiles p ON p.id = g.profile_id
             LEFT JOIN access_requests r ON r.id = g.request_id
             ORDER BY g.created_at DESC
             LIMIT 100'
        );
    }

    /** Find a valid grant by plaintext code and consume one use. */
    public static function redeem(int $profileId, string $code): array
    {
        $row = Database::first(
            'SELECT g.*, paccess.access_hours
             FROM access_grants g
             JOIN profile_access paccess ON paccess.profile_id = g.profile_id
             WHERE g.profile_id = :pid
               AND g.code_hash = :hash
               AND g.is_revoked = 0
               AND g.uses_left > 0
               AND g.expires_at > UTC_TIMESTAMP()
             LIMIT 1',
            ['pid' => $profileId, 'hash' => hash('sha256', $code)]
        );
        if ($row === null) {
            return [];
        }

        Database::query(
            'UPDATE access_grants
                SET uses_left = uses_left - 1, redeemed_at = UTC_TIMESTAMP(), redeemed_ip = :ip
              WHERE id = :id',
            ['ip' => client_ip(), 'id' => $row['id']]
        );

        Admin::audit(null, 'grant_redeemed', 'access_grant', (int) $row['id'], 'ip=' . client_ip());

        return [
            'expires_at' => Database::scalar('SELECT expires_at FROM access_grants WHERE id = :id', ['id' => $row['id']]),
            'access_hours' => (int) $row['access_hours'],
        ];
    }

    public static function revoke(int $id, int $adminId): bool
    {
        Database::query('UPDATE access_grants SET is_revoked = 1 WHERE id = :id', ['id' => $id]);
        Admin::audit($adminId, 'grant_revoked', 'access_grant', $id);
        return true;
    }

    /** Expire overdue grants (cron helper). */
    public static function expireOverdue(): int
    {
        $stmt = Database::query(
            'UPDATE access_grants SET is_revoked = 1 WHERE expires_at < UTC_TIMESTAMP() AND is_revoked = 0'
        );
        return $stmt->rowCount();
    }
}
