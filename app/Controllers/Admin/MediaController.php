<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Models\Admin;
use App\Models\GalleryImage;
use App\Models\MediaAsset;
use App\Models\Profile;
use App\Services\Uploader;

final class MediaController
{
    public const CATS = [
        'welcome'  => ['🐱', 'Welcome kitten', 'Hops and holds a gift'],
        'crying'   => ['😿', 'Crying cat', 'Fountain tears and a tiny tissue'],
        'begging'  => ['🥺', 'Begging cat', 'Puppy eyes zoom, paws together'],
        'hiding'   => ['🙈', 'Hiding cat', 'Peeks from the edge of a card'],
        'rolling'  => ['😹', 'Rolling cat', 'Rolls across the screen'],
        'angry'    => ['😾', 'Angry cat', 'Red cheeks, steam and stomps'],
        'dancing'  => ['💃', 'Forgiven cat', 'Disco spin with hearts'],
        'love'     => ['😻', 'Love cat', 'Holds a heart, hearts float up'],
        'sleepy'   => ['😴', 'Sleepy cat', 'Zzz bubbles'],
        'peek'     => ['👀', 'Peek cat', 'Pops out at the screen edges'],
    ];

    public function show(int $id): void
    {
        $profile = Profile::find($id);
        if ($profile === null) {
            redirect(url('/admin/profiles'));
        }
        view('admin/media', [
            'title'   => 'Media: ' . $profile['display_name'],
            'active'  => 'profiles',
            'profile' => $profile,
            'images'  => GalleryImage::forProfile($id),
            'assets'  => MediaAsset::forProfile($id),
            'cats'    => self::CATS,
            'flash'   => pull_flash(),
        ], 'layouts/admin');
    }

    // ---------- gallery ----------

    public function upload(int $id): void
    {
        if (!check_csrf() || Profile::find($id) === null) {
            redirect(url('/admin/profiles'));
        }

        $files = (array) ($_FILES['photos'] ?? []);
        if (isset($files['name']) && !is_array($files['name']) && $files['error'] === UPLOAD_ERR_NO_FILE) {
            flash('error', 'Choose some photos first 🖼️');
            redirect(url('/admin/profiles/' . $id . '/media'));
        }

        $count = 0;
        foreach ($this->normalise($files) as $file) {
            $stored = Uploader::image($id, $file);
            if ($stored !== null) {
                GalleryImage::add($id, $stored['file'], $stored['thumb']);
                $count++;
            }
        }
        if (empty($_FILES['photos']) || $count === 0) {
            flash('error', 'No valid images uploaded — use JPG, PNG or WebP under 5 MB 🐾');
            redirect(url('/admin/profiles/' . $id . '/media'));
        }

        Admin::audit((int) (Auth::user()['id'] ?? 0), 'photos_uploaded', 'profile', $id, (string) $count);
        flash('success', $count . ' photo(s) uploaded 🎉');
        redirect(url('/admin/profiles/' . $id . '/media'));
    }

    public function imageUpdate(int $id, int $gid): void
    {
        if (check_csrf() && Profile::find($id) !== null) {
            GalleryImage::update($id, $gid, [
                'caption'     => mb_substr(trim((string) ($_POST['caption'] ?? '')), 0, 255),
                'alt'         => mb_substr(trim((string) ($_POST['alt_text'] ?? '')), 0, 255),
                'memory_note' => mb_substr(trim((string) ($_POST['memory_note'] ?? '')), 0, 1000),
                'taken_on'    => (string) ($_POST['taken_on'] ?? ''),
                'is_active'   => isset($_POST['is_active']) ? 1 : 0,
            ]);
        }
        redirect(url('/admin/profiles/' . $id . '/media'));
    }

    public function imageDelete(int $id, int $gid): void
    {
        if (check_csrf()) {
            $row = GalleryImage::delete($id, $gid);
            if ($row !== false && $row !== null) {
                Uploader::remove((string) $row['file_name']);
                if (!empty($row['thumb_name'])) {
                    Uploader::remove((string) $row['thumb_name']);
                }
            }
            flash('success', 'Photo removed 🧹');
        }
        redirect(url('/admin/profiles/' . $id . '/media'));
    }

    public function imageMove(int $id, int $gid): void
    {
        if (check_csrf()) {
            GalleryImage::move($id, $gid, (string) ($_POST['direction'] ?? 'up') === 'up' ? 'up' : 'down');
        }
        redirect(url('/admin/profiles/' . $id . '/media'));
    }

    public function imageCover(int $id, int $gid): void
    {
        if (check_csrf()) {
            GalleryImage::setCover($id, $gid);
            flash('success', 'Cover star updated ⭐');
        }
        redirect(url('/admin/profiles/' . $id . '/media'));
    }

    // ---------- audio ----------

    public function audioUpload(int $id): void
    {
        if (!check_csrf() || Profile::find($id) === null) {
            redirect(url('/admin/profiles'));
        }

        $kind = ($_POST['kind'] ?? 'music') === 'sfx' ? 'sfx' : 'music';
        $file = $_FILES['audio'] ?? [];
        $stored = Uploader::audio($id, (array) $file, $kind === 'music' ? 12 : 4);

        if ($stored === null) {
            flash('error', 'Upload failed — use MP3/OGG (' . ($kind === 'music' ? 'up to 12 MB' : 'up to 4 MB') . ') 🎵');
            redirect(url('/admin/profiles/' . $id . '/media'));
        }

        MediaAsset::add(
            $id, $kind, $stored['file'],
            mb_substr(trim((string) ($_POST['label'] ?? ($kind === 'music' ? 'Background music' : 'Sound effect'))), 0, 120),
            $kind === 'sfx' && $stored['ext'] === 'wav' ? 'audio/wav' : 'audio/mpeg'
        );
        Admin::audit((int) (Auth::user()['id'] ?? 0), 'audio_uploaded', 'profile', $id, $kind);

        flash('success', ($kind === 'music' ? 'Background music' : 'Sound effect') . ' uploaded 🎵');
        redirect(url('/admin/profiles/' . $id . '/media'));
    }

    public function audioDelete(int $id, int $aid): void
    {
        if (check_csrf()) {
            $row = MediaAsset::delete($id, $aid);
            if ($row) {
                Uploader::remove((string) $row['file_name']);
            }
            flash('success', 'Audio removed 🧹');
        }
        redirect(url('/admin/profiles/' . $id . '/media'));
    }

    /** Flatten PHP's multi-file array structure. */
    private function normalise(array $files): array
    {
        if (!isset($files['name']) || !is_array($files['name'])) {
            return isset($files['tmp_name']) && $files['tmp_name'] !== '' ? [$files] : [];
        }
        $out = [];
        foreach ($files['name'] as $i => $name) {
            $out[] = [
                'name'     => $name,
                'type'     => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i] ?? '',
                'error'    => $files['error'][$i] ?? 1,
                'size'     => $files['size'][$i] ?? 0,
            ];
        }
        return $out;
    }
}
