<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use Throwable;

final class Profile
{
    public const STATUSES = ['draft', 'scheduled', 'published', 'paused', 'archived'];

    public static function all(?string $status = null): array
    {
        $sql = 'SELECT * FROM birthday_profiles WHERE deleted_at IS NULL';
        $params = [];
        if ($status !== null && in_array($status, self::STATUSES, true)) {
            $sql .= ' AND status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY created_at DESC';
        return Database::all($sql, $params);
    }

    public static function find(int $id): ?array
    {
        return Database::first(
            'SELECT * FROM birthday_profiles WHERE id = :id AND deleted_at IS NULL LIMIT 1',
            ['id' => $id]
        );
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::first(
            'SELECT * FROM birthday_profiles WHERE slug = :slug AND deleted_at IS NULL LIMIT 1',
            ['slug' => $slug]
        );
    }

    public static function create(array $data, int $adminId): ?int
    {
        try {
            return Database::transaction(static function () use ($data, $adminId): int {
                Database::query(
                    'INSERT INTO birthday_profiles
                       (display_name, slug, birthday_at, timezone, status, publish_at,
                        block_before_birthday, allow_early_peek, allow_share, created_by)
                     VALUES
                       (:name, :slug, :birthday, :tz, :status, :publish_at,
                        :block, :peek, :share, :admin)',
                    [
                        'name'       => $data['display_name'],
                        'slug'       => $data['slug'],
                        'birthday'   => $data['birthday_at'],
                        'tz'         => $data['timezone'],
                        'status'     => $data['status'],
                        'publish_at' => $data['publish_at'],
                        'block'      => $data['block_before_birthday'],
                        'peek'       => $data['allow_early_peek'],
                        'share'      => $data['allow_share'],
                        'admin'      => $adminId,
                    ]
                );
                $id = (int) Database::scalar(
                    'SELECT id FROM birthday_profiles WHERE slug = :slug LIMIT 1',
                    ['slug' => $data['slug']]
                );

                Database::query(
                    'INSERT INTO profile_access (profile_id, password_hash, access_hours, max_reentry_uses, reentry_policy)
                     VALUES (:pid, :hash, :hours, :uses, :policy)',
                    [
                        'pid'    => $id,
                        'hash'   => password_hash($data['password'], PASSWORD_DEFAULT),
                        'hours'  => $data['access_hours'],
                        'uses'   => $data['max_reentry_uses'],
                        'policy' => $data['reentry_policy'],
                    ]
                );

                Content::ensureRow($id);
                Database::query(
                    'INSERT INTO profile_theme_settings (profile_id) VALUES (:pid)',
                    ['pid' => $id]
                );

                // Friendly starter content so preview works immediately.
                Content::update($id, [
                    'welcome_message'      => 'Meow! Something special is waiting for you… 🎁',
                    'gift_categories'      => 'Flowers, Cake, Trip, Book',
                    'complaint_placeholder' => "Tell me everything — your feelings matter to me 💌",
                ]);
                Sorry::add($id, 'Okay okay — I know I messed up… please look at this little cat 🥺');
                Sorry::add($id, 'I even brought fish as an apology 🐟');
                Wish::add($id, 'Happy Birthday to my favourite human on the planet! 🎂💖');

                Admin::audit($adminId, 'profile_created', 'profile', $id, 'slug=' . $data['slug']);
                return $id;
            });
        } catch (Throwable $e) {
            error_log('[profile.create] ' . $e->getMessage());
            return null;
        }
    }

    public static function update(int $id, array $data): bool
    {
        $sql = 'UPDATE birthday_profiles
                   SET display_name = :name, slug = :slug, birthday_at = :birthday,
                       timezone = :tz, status = :status, publish_at = :publish_at,
                       block_before_birthday = :block, allow_early_peek = :peek,
                       allow_share = :share
                 WHERE id = :id';
        Database::query($sql, [
            'name'       => $data['display_name'],
            'slug'       => $data['slug'],
            'birthday'   => $data['birthday_at'],
            'tz'         => $data['timezone'],
            'status'     => $data['status'],
            'publish_at' => $data['publish_at'],
            'block'      => $data['block_before_birthday'],
            'peek'       => $data['allow_early_peek'],
            'share'      => $data['allow_share'],
            'id'         => $id,
        ]);

        if (!empty($data['password'])) {
            Database::query(
                'UPDATE profile_access SET password_hash = :hash WHERE profile_id = :id',
                ['hash' => password_hash($data['password'], PASSWORD_DEFAULT), 'id' => $id]
            );
        }
        Database::query(
            'UPDATE profile_access SET access_hours = :hours, max_reentry_uses = :uses, reentry_policy = :policy
             WHERE profile_id = :id',
            ['hours' => $data['access_hours'], 'uses' => $data['max_reentry_uses'], 'policy' => $data['reentry_policy'], 'id' => $id]
        );
        return true;
    }

    public static function setStatus(int $id, string $status, ?int $adminId = null): bool
    {
        Database::query(
            'UPDATE birthday_profiles SET status = :status WHERE id = :id',
            ['status' => $status, 'id' => $id]
        );
        if ($adminId !== null) {
            Admin::audit($adminId, 'profile_status_' . $status, 'profile', $id);
        }
        return true;
    }

    public static function archive(int $id): bool
    {
        Database::query('UPDATE birthday_profiles SET status = :s WHERE id = :id', ['s' => 'archived', 'id' => $id]);
        return true;
    }

    public static function restore(int $id): bool
    {
        Database::query("UPDATE birthday_profiles SET status = 'draft' WHERE id = :id", ['id' => $id]);
        return true;
    }

    public static function destroy(int $id): bool
    {
        Database::query('DELETE FROM birthday_profiles WHERE id = :id', ['id' => $id]); // cascades owned rows + logs
        // Remove uploaded media folder (best effort).
        self::deleteFolder(BASE_PATH . '/public/uploads/p' . $id);
        return true;
    }

    private static function deleteFolder(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? self::deleteFolder($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    /** Slug from a display name, guaranteed unique. */
    public static function uniqueSlug(string $name, int $ignoreId = 0): string
    {
        $base = strtolower(trim($name));
        $base = preg_replace('/[^a-z0-9]+/i', '-', $base) ?? '';
        $base = trim($base, '-');
        if ($base === '') {
            $base = 'birthday';
        }
        if (strlen($base) > 100) {
            $base = substr($base, 0, 100);
        }

        $slug = $base;
        $n = 2;
        while (self::slugTaken($slug, $ignoreId)) {
            $slug = $base . '-' . $n++;
        }
        return $slug;
    }

    public static function slugTaken(string $slug, int $ignoreId = 0): bool
    {
        return (bool) Database::scalar(
            'SELECT COUNT(*) FROM birthday_profiles WHERE slug = :slug AND id != :id',
            ['slug' => $slug, 'id' => $ignoreId]
        );
    }

    public static function accessSetting(int $id): ?array
    {
        return Database::first('SELECT * FROM profile_access WHERE profile_id = :id LIMIT 1', ['id' => $id]);
    }
}
