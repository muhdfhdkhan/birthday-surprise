<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use Throwable;

final class VisitorResponse
{
    public static function add(int $profileId, string $type, string $body, ?string $category, ?string $reaction, ?string $visitorUid): void
    {
        Database::query(
            'INSERT INTO visitor_responses (profile_id, visitor_uid, type, body, category, reaction, status, ip_address, session_id)
             VALUES (:pid, :vid, :type, :body, :cat, :react, "unread", :ip, :sid)',
            [
                'pid'   => $profileId,
                'vid'   => $visitorUid,
                'type'  => in_array($type, ['gift', 'complaint', 'reaction'], true) ? $type : 'reaction',
                'body'  => $body !== '' ? mb_substr($body, 0, 1000) : null,
                'cat'   => $category,
                'react' => $reaction !== null ? mb_substr($reaction, 0, 16) : null,
                'ip'    => client_ip(),
                'sid'   => session_id(),
            ]
        );
    }

    public static function unreadCount(): int
    {
        return (int) (Database::scalar(
            'SELECT COUNT(*) FROM visitor_responses WHERE status = "unread"'
        ) ?? 0);
    }

    /** Row counts per inbox status ('unread', 'read', 'archived'). */
    public static function countsByStatus(): array
    {
        $out = ['unread' => 0, 'read' => 0, 'archived' => 0];
        try {
            foreach (Database::all('SELECT status, COUNT(*) AS n FROM visitor_responses GROUP BY status') as $r) {
                $out[(string) $r['status']] = (int) $r['n'];
            }
        } catch (Throwable) {
            // storage hiccup — tabs simply show zero
        }
        return $out;
    }

    /** Whitelisted filter value (empty string = no filter). */
    public static function cleanFilter(mixed $value, array $allowed): string
    {
        $v = is_string($value) ? $value : '';
        return in_array($v, $allowed, true) ? $v : '';
    }

    /** Inbox listing with profile / type / status filters (all optional). */
    public static function search(array $filters, int $limit = 100): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['profile'])) {
            $where[] = 'r.profile_id = :pid';
            $params['pid'] = (int) $filters['profile'];
        }
        if (!empty($filters['type'])) {
            $where[] = 'r.type = :type';
            $params['type'] = (string) $filters['type'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'r.status = :status';
            $params['status'] = (string) $filters['status'];
        }

        $sql = 'SELECT r.*, p.display_name AS profile_name, p.slug AS profile_slug
                FROM visitor_responses r
                LEFT JOIN birthday_profiles p ON p.id = r.profile_id'
            . (count($where) > 0 ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY FIELD(r.status, "unread", "read", "archived"), r.created_at DESC
               LIMIT :lim';
        $params['lim'] = max(1, min(500, $limit));

        return Database::all($sql, $params);
    }

    /** Move a letter between inbox states. Returns whether a row changed. */
    public static function setStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['unread', 'read', 'archived'], true)) {
            return false;
        }
        $exists = (int) (Database::scalar(
            'SELECT COUNT(*) FROM visitor_responses WHERE id = :id',
            ['id' => $id]
        ) ?? 0);
        if ($exists === 0) {
            return false;
        }
        Database::query(
            'UPDATE visitor_responses SET status = :st WHERE id = :id',
            ['st' => $status, 'id' => $id]
        );
        return true;
    }

    /** Hard-delete a letter. */
    public static function destroy(int $id): void
    {
        Database::query('DELETE FROM visitor_responses WHERE id = :id', ['id' => $id]);
    }
}
