<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Admin
{
    /**
     * A real (unusable) bcrypt hash. Used to equalise response timing when
     * the username does not exist, so login timing cannot enumerate users.
     */
    public const DUMMY_HASH = '$2y$12$hob.AQBVEh4psOGMZsSkxOQNe9x3Zz53lz58kFGepNBvDxxnSP0Z6';

    /** Find an active admin row by username or email. */
    public static function findByUsername(string $identity): ?array
    {
        return Database::first(
            'SELECT * FROM admins WHERE username = :id1 OR email = :id2 LIMIT 1',
            ['id1' => $identity, 'id2' => $identity]
        );
    }

    /** Stamp the last login time / IP after a successful sign-in. */
    public static function markLogin(int $id, string $ip): void
    {
        Database::query(
            'UPDATE admins SET last_login_at = UTC_TIMESTAMP(), last_login_ip = :ip WHERE id = :id',
            ['ip' => $ip, 'id' => $id]
        );
    }

    /** Record an admin-area action in the audit log. */
    public static function audit(?int $adminId, string $action, string $entity = '', ?int $entityId = null, string $details = ''): void
    {
        Database::query(
            'INSERT INTO admin_audit_logs (admin_id, action, entity, entity_id, ip_address, details)
             VALUES (:admin_id, :action, :entity, :entity_id, :ip, :details)',
            [
                'admin_id'  => $adminId,
                'action'    => substr($action, 0, 80),
                'entity'    => substr($entity, 0, 60),
                'entity_id' => $entityId,
                'ip'        => client_ip(),
                'details'   => $details !== '' ? substr($details, 0, 500) : null,
            ]
        );
    }
}
