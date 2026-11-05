<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class MediaAsset
{
    public static function forProfile(int $profileId, ?string $kind = null): array
    {
        $sql = 'SELECT * FROM media_assets WHERE profile_id = :id';
        $params = ['id' => $profileId];
        if ($kind !== null && in_array($kind, ['music', 'sfx', 'animation'], true)) {
            $sql .= ' AND kind = :kind';
            $params['kind'] = $kind;
        }
        return Database::all($sql . ' ORDER BY kind ASC, id DESC', $params);
    }

    public static function music(int $profileId): ?array
    {
        return Database::first(
            "SELECT * FROM media_assets WHERE profile_id = :id AND kind = 'music' AND is_active = 1 LIMIT 1",
            ['id' => $profileId]
        );
    }

    public static function add(int $profileId, string $kind, string $fileName, string $label, string $mimeType): void
    {
        // One background track per profile: replace the existing one.
        if ($kind === 'music') {
            Database::query("DELETE FROM media_assets WHERE profile_id = :id AND kind = 'music'", ['id' => $profileId]);
        }
        Database::query(
            'INSERT INTO media_assets (profile_id, kind, file_name, label, mime_type)
             VALUES (:pid, :kind, :file, :label, :mime)',
            ['pid' => $profileId, 'kind' => $kind, 'file' => $fileName, 'label' => $label, 'mime' => $mimeType]
        );
    }

    public static function get(int $profileId, int $id): ?array
    {
        return Database::first(
            'SELECT * FROM media_assets WHERE id = :id AND profile_id = :pid LIMIT 1',
            ['id' => $id, 'pid' => $profileId]
        );
    }

    public static function delete(int $profileId, int $id): bool
    {
        $row = self::get($profileId, $id);
        if ($row === null) {
            return false;
        }
        Database::query('DELETE FROM media_assets WHERE id = :id', ['id' => $id]);
        return true;
    }
}
