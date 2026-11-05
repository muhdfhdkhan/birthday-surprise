<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Content
{
    public const SECTIONS = ['welcome', 'sorry', 'reveal', 'memories', 'letter', 'replies', 'finale'];
    public const SECRET_RULES = ['after_sorry', 'after_candles', 'manual', 'never'];

    public static function ensureRow(int $profileId): void
    {
        Database::query(
            'INSERT IGNORE INTO birthday_content (profile_id) VALUES (:pid)',
            ['pid' => $profileId]
        );
    }

    public static function get(int $profileId): ?array
    {
        return Database::first('SELECT * FROM birthday_content WHERE profile_id = :id LIMIT 1', ['id' => $profileId]);
    }

    /** Update the plain-text content fields (whitelist of keys). */
    public static function update(int $profileId, array $data): void
    {
        $fields = [
            'welcome_message', 'gift_categories', 'complaint_placeholder',
            'letter_text', 'secret_message', 'final_message', 'post_birthday_message',
        ];
        $sets = [];
        $params = ['id' => $profileId];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        if ($sets === []) {
            return;
        }
        Database::query('UPDATE birthday_content SET ' . implode(', ', $sets) . ' WHERE profile_id = :id', $params);
    }

    /** Save section toggles + secret rule. */
    public static function updateMeta(int $profileId, array $sections, string $secretRule, int $sorryRequired): void
    {
        $clean = array_values(array_intersect($sections, self::SECTIONS));
        Database::query(
            'UPDATE birthday_content
                SET sections_enabled = :sections, secret_unlock_rule = :rule, sorry_required = :req
              WHERE profile_id = :id',
            [
                'sections' => implode(',', $clean),
                'rule'     => in_array($secretRule, self::SECRET_RULES, true) ? $secretRule : 'after_sorry',
                'req'      => $sorryRequired ? 1 : 0,
                'id'       => $profileId,
            ]
        );
    }
}
