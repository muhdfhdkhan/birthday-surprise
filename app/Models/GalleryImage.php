<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class GalleryImage
{
    public static function forProfile(int $profileId, bool $onlyActive = false): array
    {
        $sql = 'SELECT * FROM gallery_images WHERE profile_id = :id';
        if ($onlyActive) {
            $sql .= ' AND is_active = 1';
        }
        return Database::all($sql . ' ORDER BY sort_order ASC, id ASC', ['id' => $profileId]);
    }

    public static function cover(int $profileId): ?array
    {
        return Database::first(
            'SELECT * FROM gallery_images WHERE profile_id = :id AND is_cover = 1 AND is_active = 1 LIMIT 1',
            ['id' => $profileId]
        );
    }

    public static function add(int $profileId, string $fileName, ?string $thumbName): void
    {
        $max = (int) (Database::scalar(
            'SELECT COALESCE(MAX(sort_order), 0) FROM gallery_images WHERE profile_id = :id',
            ['id' => $profileId]
        ) ?? 0);
        Database::query(
            'INSERT INTO gallery_images (profile_id, file_name, thumb_name, sort_order) VALUES (:pid, :file, :thumb, :sort)',
            ['pid' => $profileId, 'file' => $fileName, 'thumb' => $thumbName, 'sort' => $max + 1]
        );
    }

    public static function get(int $profileId, int $id): ?array
    {
        return Database::first(
            'SELECT * FROM gallery_images WHERE id = :id AND profile_id = :pid LIMIT 1',
            ['id' => $id, 'pid' => $profileId]
        );
    }

    public static function update(int $profileId, int $id, array $data): void
    {
        Database::query(
            'UPDATE gallery_images SET caption = :caption, alt_text = :alt, memory_note = :note,
                    taken_on = :taken, is_active = :active
              WHERE id = :id AND profile_id = :pid',
            [
                'caption' => $data['caption'], 'alt' => $data['alt'],
                'note' => $data['memory_note'], 'taken' => $data['taken_on'] ?: null,
                'active' => $data['is_active'],
                'id' => $id, 'pid' => $profileId,
            ]
        );
    }

    public static function delete(int $profileId, int $id): bool
    {
        $row = self::get($profileId, $id);
        if ($row === null) {
            return false;
        }
        Database::query('DELETE FROM gallery_images WHERE id = :id', ['id' => $id]);
        return true; // caller unlinks files
    }

    public static function setCover(int $profileId, int $id): void
    {
        Database::query('UPDATE gallery_images SET is_cover = 0 WHERE profile_id = :pid', ['pid' => $profileId]);
        Database::query('UPDATE gallery_images SET is_cover = 1 WHERE id = :id AND profile_id = :pid', ['id' => $id, 'pid' => $profileId]);
    }

    public static function move(int $profileId, int $id, string $direction): void
    {
        $rows = self::forProfile($profileId);
        $index = null;
        foreach ($rows as $i => $row) {
            if ((int) $row['id'] === $id) {
                $index = $i;
                break;
            }
        }
        if ($index === null) {
            return;
        }
        $target = $direction === 'up' ? $index - 1 : $index + 1;
        if ($target < 0 || $target >= count($rows)) {
            return;
        }
        Database::query('UPDATE gallery_images SET sort_order = :o WHERE id = :id', ['o' => $target + 1, 'id' => $rows[$index]['id']]);
        Database::query('UPDATE gallery_images SET sort_order = :o WHERE id = :id', ['o' => $index + 1, 'id' => $rows[$target]['id']]);
    }
}
