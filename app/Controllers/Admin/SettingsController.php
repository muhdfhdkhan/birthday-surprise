<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Models\Admin;
use App\Models\Settings;

final class SettingsController
{
    /** Editable settings: key => [kind, ...rules]. Everything is whitelist-driven. */
    private const FIELDS = [
        'app_name'              => ['text', 1, 80],
        'app_timezone'          => ['timezone'],
        'maintenance_mode'      => ['bool'],
        'log_retention_days'    => ['int', 7, 365],
        'uploads_max_mb'        => ['int', 1, 10],
        'uploads_allowed_types' => ['imagetypes'],
        'access_window_hours'   => ['int', 1, 168],
        'visitor_privacy_note'  => ['text', 0, 300],
    ];

    /** GET /admin/settings */
    public function index(): void
    {
        $db = config('db');
        view('admin/settings', [
            'title'    => 'Settings · Birthday Surprise',
            'active'   => 'settings',
            'settings' => Settings::all(),
            'defaults' => [
                'app_name'              => 'Birthday Surprise',
                'app_timezone'          => 'UTC',
                'maintenance_mode'      => '0',
                'log_retention_days'    => '90',
                'uploads_max_mb'        => '5',
                'uploads_allowed_types' => 'jpeg,jpg,png,webp',
                'access_window_hours'   => '24',
                'visitor_privacy_note'  => 'We log visits and IP addresses to keep this page safe.',
            ],
            'env' => [
                'php'     => PHP_VERSION,
                'db'      => (string) ($db['name'] ?? '?'),
                'server'  => PHP_OS_FAMILY,
                'uploads' => ini_get('upload_max_filesize') ?: '?',
            ],
            'flash' => pull_flash(),
        ], 'layouts/admin');
    }

    /** POST /admin/settings */
    public function save(): void
    {
        if (!check_csrf()) {
            redirect(url('/admin/settings'));
        }

        $saved = [];
        foreach (self::FIELDS as $key => $field) {
            $kind = $field[0];
            $a    = $field[1] ?? null;
            $b    = $field[2] ?? null;
            $raw  = trim((string) ($_POST[$key] ?? ''));
            $err  = null;

            switch ($kind) {
                case 'bool':
                    $raw = in_array(strtolower((string) ($_POST[$key] ?? '')), ['1', 'on', 'true'], true) ? '1' : '0';
                    break;

                case 'int':
                    $n = (int) $raw;
                    if ($n < $a || $n > $b) {
                        $err = "must be {$a}–{$b}";
                    } else {
                        $raw = (string) $n;
                    }
                    break;

                case 'text':
                    if (mb_strlen($raw) < $a) { $err = "at least {$a} characters"; }
                    if (mb_strlen($raw) > $b) { $raw = mb_substr($raw, 0, $b); }
                    break;

                case 'timezone':
                    if (!in_array($raw, \DateTimeZone::listIdentifiers(), true)) {
                        $err = 'unknown timezone';
                    }
                    break;

                case 'imagetypes':
                    $allowed     = ['jpg', 'jpeg', 'png', 'webp'];
                    $picked      = array_values(array_intersect(
                        array_map('strtolower', array_map('trim', explode(',', $raw))),
                        $allowed
                    ));
                    if ($picked === []) {
                        $err = 'pick at least one type';
                        $picked = $allowed;
                    }
                    $raw = implode(',', $picked);
                    break;
            }

            if ($err !== null) {
                flash('error', 'Setting "' . $key . '" ' . $err . ' 🐱');
                continue;
            }
            Settings::set($key, $raw);
            $saved[$key] = $raw;
        }

        if (count($saved) > 0) {
            Admin::audit((int) (Auth::user()['id'] ?? 0), 'settings_save', 'app_settings', null, 'keys: ' . implode(',', array_keys($saved)));
            flash('success', 'Settings saved 🎉');
        }
        redirect(url('/admin/settings'));
    }
}
