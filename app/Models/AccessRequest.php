<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class AccessRequest
{
    public static function all(int $limit = 100): array
    {
        return Database::all(
            'SELECT r.*, p.display_name AS profile_name, p.slug AS profile_slug
             FROM access_requests r
             LEFT JOIN birthday_profiles p ON p.id = r.profile_id
             ORDER BY FIELD(r.status, "pending", "approved", "rejected", "expired"), r.created_at DESC
             LIMIT :lim',
            ['lim' => $limit]
        );
    }

    public static function pendingCount(): int
    {
        return (int) (Database::scalar(
            "SELECT COUNT(*) FROM access_requests WHERE status = 'pending'"
        ) ?? 0);
    }

    public static function get(int $id): ?array
    {
        return Database::first(
            'SELECT r.*, p.display_name AS profile_name, p.slug AS profile_slug
             FROM access_requests r
             LEFT JOIN birthday_profiles p ON p.id = r.profile_id
             WHERE r.id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public static function create(int $profileId, ?string $visitorUid, string $note, string $ip): void
    {
        Database::query(
            'INSERT INTO access_requests (profile_id, visitor_uid, session_id, note, status, ip_address)
             VALUES (:pid, :vid, :sid, :note, "pending", :ip)',
            [
                'pid'  => $profileId,
                'vid'  => $visitorUid,
                'sid'  => session_id(),
                'note' => $note !== '' ? mb_substr($note, 0, 500) : null,
                'ip'   => $ip,
            ]
        );
    }

    public static function approve(int $id, int $adminId, int $durationHours, int $maxUses): array
    {
        $row = self::get($id);
        if ($row === null || $row['status'] !== 'pending') {
            return [];
        }

        Database::query(
            'UPDATE access_requests
                SET status = "approved", decided_by = :admin, decided_at = UTC_TIMESTAMP(),
                    expires_at = UTC_TIMESTAMP() + INTERVAL :hours HOUR
              WHERE id = :id',
            ['admin' => $adminId, 'hours' => $durationHours, 'id' => $id]
        );

        $code   = strtoupper(bin2hex(random_bytes(8)));           // 16 hex chars
        $code   = implode('-', str_split($code, 4));             // XXXX-XXXX-XXXX-XXXX
        $codeHash = hash('sha256', $code);

        Database::query(
            'INSERT INTO access_grants (profile_id, request_id, code_hash, max_uses, uses_left, expires_at, created_by)
             VALUES (:pid, :rid, :hash, :max, :left, UTC_TIMESTAMP() + INTERVAL :hours HOUR, :admin)',
            [
                'pid' => (int) $row['profile_id'], 'rid' => $id, 'hash' => $codeHash,
                'max' => $maxUses, 'left' => $maxUses, 'hours' => $durationHours, 'admin' => $adminId,
            ]
        );

        Admin::audit($adminId, 'access_request_approved', 'access_request', $id, 'hours=' . $durationHours);
        return ['profile_id' => (int) $row['profile_id'], 'code' => $code, 'expires_in_hours' => $durationHours];
    }

    public static function reject(int $id, int $adminId, string $reason): bool
    {
        $row = self::get($id);
        if ($row === null || $row['status'] !== 'pending') {
            return false;
        }
        Database::query(
            'UPDATE access_requests
                SET status = "rejected", decided_by = :admin, decided_at = UTC_TIMESTAMP(), reason = :reason
              WHERE id = :id',
            [
                'admin' => $adminId,
                'reason' => $reason !== '' ? mb_substr($reason, 0, 255) : null,
                'id' => $id,
            ]
        );
        Admin::audit($adminId, 'access_request_rejected', 'access_request', $id, $reason);
        return true;
    }
}
