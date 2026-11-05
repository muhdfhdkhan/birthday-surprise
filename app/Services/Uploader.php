<?php

declare(strict_types=1);

namespace App\Services;

final class Uploader
{
    private const IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'webp'];
    private const AUDIO_TYPES = ['mp3', 'ogg', 'wav'];
    private const IMAGE_MIMES = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
    ];

    /** Admin-configurable image cap (app_settings.uploads_max_mb, clamped 1–10). */
    private static function maxImageBytes(): int
    {
        $mb = (int) (\App\Models\Settings::get('uploads_max_mb', '5') ?? 5);
        return max(1, min(10, $mb === 0 ? 5 : $mb)) * 1024 * 1024;
    }

    /** Allowed image extensions (white-list intersect with app_settings). */
    private static function imageTypes(): array
    {
        $raw = (string) (\App\Models\Settings::get('uploads_allowed_types', '') ?? '');
        $picked = array_values(array_intersect(
            array_map('trim', array_map('strtolower', explode(',', $raw))),
            self::IMAGE_TYPES
        ));
        return $picked !== [] ? $picked : self::IMAGE_TYPES;
    }

    /** Store an uploaded image + thumbnail. Returns url-style names or null. */
    public static function image(int $profileId, array $file): ?array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        if (($file['size'] ?? 0) <= 0 || $file['size'] > self::maxImageBytes()) {
            return null;
        }

        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, self::imageTypes(), true)) {
            return null;
        }

        $dir = self::dir('p' . $profileId . '/gallery');
        $original = tempnam(sys_get_temp_dir(), 'up');
        if ($original === false) {
            return null;
        }
        @move_uploaded_file($file['tmp_name'], $original);

        // Content sniffing: the bytes must match a real image.
        $info = @getimagesize($original);
        if ($info === false || ($info['mime'] ?? '') !== (self::IMAGE_MIMES[$ext] ?? '')) {
            @unlink($original);
            return null;
        }
        if ($ext === 'jpg' || $ext === 'jpeg') {
            $head = (string) file_get_contents($original, false, null, 0, 3);
            if (bin2hex($head) !== 'ffd8ff') {
                @unlink($original);
                return null;
            }
        }

        $name  = self::random('img', $ext);
        $thumb = self::random('t', 'jpg');
        $dest  = $dir . '/' . $name;

        if (!@rename($original, $dest)) {
            @unlink($original);
            return null;
        }

        $thumbPath = $dir . '/' . $thumb;
        if (!self::makeThumb($dest, $thumbPath, 480, $info['mime'])) {
            @copy($dest, $thumbPath);
        }

        return ['file' => 'p' . $profileId . '/gallery/' . $name, 'thumb' => 'p' . $profileId . '/gallery/' . $thumb];
    }

    /** Store an uploaded audio file. Returns file name or null. */
    public static function audio(int $profileId, array $file, int $maxMb = 12): ?array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        if (($file['size'] ?? 0) <= 0 || $file['size'] > $maxMb * 1024 * 1024) {
            return null;
        }
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, self::AUDIO_TYPES, true)) {
            return null;
        }

        $dir = self::dir('p' . $profileId . '/audio');
        $original = tempnam(sys_get_temp_dir(), 'aup');
        if ($original === false) {
            return null;
        }
        @move_uploaded_file($file['tmp_name'], $original);

        // Content sniffing by signature.
        $head = (string) file_get_contents($original, false, null, 0, 12);
        $sig  = bin2hex($head);
        $ok   = $ext === 'mp3' && (str_starts_with($sig, '494433') || str_starts_with($sig, 'fffb') || str_starts_with($sig, 'fff3') || str_starts_with($sig, 'fffa'))
             || $ext === 'ogg' && str_starts_with($sig, '4f676753')
             || $ext === 'wav' && str_contains($head, 'WAVE');
        if (!$ok) {
            @unlink($original);
            return null;
        }

        $name = self::random('aud', $ext);
        if (!@rename($original, $dir . '/' . $name)) {
            @unlink($original);
            return null;
        }
        return ['file' => 'p' . $profileId . '/audio/' . $name, 'ext' => $ext];
    }

    /** Remove a stored file under public/uploads (path-guarded). */
    public static function remove(string $relative): void
    {
        $base = realpath(BASE_PATH . '/public/uploads');
        $path = realpath(BASE_PATH . '/public/uploads/' . $relative);
        if ($base !== false && $path !== false && str_starts_with($path, $base) && is_file($path)) {
            @unlink($path);
        }
    }

    private static function dir(string $sub): string
    {
        $dir = BASE_PATH . '/public/uploads/' . $sub;
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    private static function random(string $prefix, string $ext): string
    {
        return $prefix . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    }

    private static function makeThumb(string $src, string $dest, int $maxW, string $mime): bool
    {
        if (!extension_loaded('gd')) {
            return false;
        }
        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($src),
            'image/png'  => @imagecreatefrompng($src),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false,
            default      => false,
        };
        if ($img === false) {
            return false;
        }
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w <= $maxW) {
            @copy($src, $dest);   // no GD handles were reaped — free() is a no-op since PHP 8.0
            return true;
        }
        $tw = $maxW;
        $th = (int) round($h * $maxW / $w);

        $thumb = imagecreatetruecolor($tw, $th);
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagefill($thumb, 0, 0, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
        }
        imagecopyresampled($thumb, $img, 0, 0, 0, 0, $tw, $th, $w, $h);
        $ok = @imagejpeg($thumb, $dest, 82);
        return (bool) $ok;
    }
}
