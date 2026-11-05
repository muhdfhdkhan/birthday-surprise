<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Wish
{
    public const CATS = ['dancing', 'love', 'welcome', 'peek'];

    public static function forProfile(int $profileId): array
    {
        return Database::all(
            'SELECT * FROM birthday_messages WHERE profile_id = :id ORDER BY sort_order ASC, id ASC',
            ['id' => $profileId]
        );
    }

    public static function add(int $profileId, string $body, string $catAction = 'dancing'): void
    {
        $max = (int) (Database::scalar(
            'SELECT COALESCE(MAX(sort_order), 0) FROM birthday_messages WHERE profile_id = :id',
            ['id' => $profileId]
        ) ?? 0);
        Database::query(
            'INSERT INTO birthday_messages (profile_id, body, cat_action, sort_order) VALUES (:pid, :body, :cat, :sort)',
            ['pid' => $profileId, 'body' => $body, 'cat' => in_array($catAction, self::CATS, true) ? $catAction : 'dancing', 'sort' => $max + 1]
        );
    }

    public static function update(int $profileId, int $id, string $body, string $catAction): void
    {
        Database::query(
            'UPDATE birthday_messages SET body = :body, cat_action = :cat WHERE id = :id AND profile_id = :pid',
            ['body' => $body, 'cat' => $catAction, 'id' => $id, 'pid' => $profileId]
        );
    }

    public static function delete(int $profileId, int $id): void
    {
        Database::query('DELETE FROM birthday_messages WHERE id = :id AND profile_id = :pid', ['id' => $id, 'pid' => $profileId]);
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
        Database::query('UPDATE birthday_messages SET sort_order = :o WHERE id = :id', ['o' => $target + 1, 'id' => $rows[$index]['id']]);
        Database::query('UPDATE birthday_messages SET sort_order = :o WHERE id = :id', ['o' => $index + 1, 'id' => $rows[$target]['id']]);
    }
}
