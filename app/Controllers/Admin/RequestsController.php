<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Models\Admin;
use App\Models\AccessGrant;
use App\Models\AccessRequest;
use App\Models\Profile;

final class RequestsController
{
    public function index(): void
    {
        $grantInfo = $_SESSION['grant_info'] ?? null;
        unset($_SESSION['grant_info']);

        view('admin/requests', [
            'title'     => 'Access requests',
            'active'    => 'requests',
            'requests'  => AccessRequest::all(),
            'grants'    => AccessGrant::all(),
            'flash'     => pull_flash(),
            'grantInfo' => $grantInfo,
            'defaultWindowHours' => (int) (\App\Models\Settings::get('access_window_hours', '24') ?: 24),
        ], 'layouts/admin');
    }

    public function approve(int $id): void
    {
        if (!check_csrf()) {
            redirect(url('/admin/requests'));
        }

        $hours    = (int) trim((string) ($_POST['hours'] ?? ''));
        if ($hours <= 0) {
            // empty field → fall back to the configured default window
            $hours = (int) (\App\Models\Settings::get('access_window_hours', '24') ?: 24);
        }
        $uses    = (int) trim((string) ($_POST['uses'] ?? ''));

        $result = AccessRequest::approve(
            $id,
            (int) (Auth::user()['id'] ?? 0),
            max(1, min(168, $hours)),
            max(1, min(20, $uses > 0 ? $uses : 1))
        );

        if ($result === []) {
            flash('error', 'Request already decided or missing 🐱');
        } else {
            // Show the one-time code exactly once (only the hash is stored).
            $_SESSION['grant_info'] = $result;
        }
        redirect(url('/admin/requests'));
    }

    public function reject(int $id): void
    {
        if (!check_csrf()) {
            redirect(url('/admin/requests'));
        }
        AccessRequest::reject($id, (int) (Auth::user()['id'] ?? 0), trim((string) ($_POST['reason'] ?? '')));
        flash('success', 'Request rejected 🐾');
        redirect(url('/admin/requests'));
    }

    public function revoke(int $id): void
    {
        if (!check_csrf()) {
            redirect(url('/admin/requests'));
        }
        AccessGrant::revoke($id, (int) (Auth::user()['id'] ?? 0));
        flash('success', 'Access code revoked ✔');
        redirect(url('/admin/requests'));
    }

    /** Profiles with pending-request badge support for the sidebar. */
    public static function pendingBadge(): int
    {
        return AccessRequest::pendingCount();
    }
}
