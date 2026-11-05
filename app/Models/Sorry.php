<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Sorry
{
    public const CATS = ['crying', 'begging', 'hiding', 'rolling', 'angry', 'dancing', 'love'];

    public static function forProfile(int $profileId): array
    {
        return Database::all(
            'SELECT * FROM sorry_messages WHERE profile_id = :id ORDER BY sort_order ASC, id ASC',
            ['id' => $profileId]
        );
    }

    public static function add(int $profileId, string $body, string $catAction = 'begging'): void
    {
        $max = (int) (Database::scalar(
            'SELECT COALESCE(MAX(sort_order), 0) FROM sorry_messages WHERE profile_id = :id',
            ['id' => $profileId]
        ) ?? 0);
        Database::query(
            'INSERT INTO sorry_messages (profile_id, body, cat_action, sort_order) VALUES (:pid, :body, :cat, :sort)',
            ['pid' => $profileId, 'body' => $body, 'cat' => in_array($catAction, self::CATS, true) ? $catAction : 'begging', 'sort' => $max + 1]
        );
    }

    public static function update(int $profileId, int $id, string $body, string $catAction, string $forgiveLabel, string $angryLabel): void
    {
        Database::query(
            'UPDATE sorry_messages SET body = :body, cat_action = :cat,
                    forgive_label = :forgive, angry_label = :angry
              WHERE id = :id AND profile_id = :pid',
            [
                'body' => $body, 'cat' => $catAction,
                'forgive' => $forgiveLabel, 'angry' => $angryLabel,
                'id' => $id, 'pid' => $profileId,
            ]
        );
    }

    public static function delete(int $profileId, int $id): void
    {
        Database::query('DELETE FROM sorry_messages WHERE id = :id AND profile_id = :pid', ['id' => $id, 'pid' => $profileId]);
    }

    /** Swap position with the neighbour in the given direction. */
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
        $a = $rows[$index];
        $b = $rows[$target];
        Database::query('UPDATE sorry_messages SET sort_order = :o WHERE id = :id', ['o' => $target + 1, 'id' => $a['id']]);
        Database::query('UPDATE sorry_messages SET sort_order = :o WHERE id = :id', ['o' => $index + 1, 'id' => $b['id']]);
    }
}
