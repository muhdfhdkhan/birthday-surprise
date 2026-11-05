<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use Throwable;

final class DashboardController
{
    /** GET /admin — the "Welcome admin" screen. */
    public function index(): void
    {
        view('admin/dashboard', [
            'title' => 'Dashboard · Birthday Surprise',
            'admin' => Auth::user(),
            'stats' => $this->stats(),
            'flash' => pull_flash(),
        ], 'layouts/admin');
    }

    /** Small overview for the stat cards (safe even if tables are empty). */
    private function stats(): array
    {
        try {
            return [
                'profiles'   => (int) Database::scalar('SELECT COUNT(*) FROM birthday_profiles WHERE deleted_at IS NULL'),
                'published'  => (int) Database::scalar("SELECT COUNT(*) FROM birthday_profiles WHERE status = 'published' AND deleted_at IS NULL"),
                'visitors'   => (int) Database::scalar('SELECT COUNT(*) FROM visitors'),
                'requests'   => (int) Database::scalar("SELECT COUNT(*) FROM access_requests WHERE status = 'pending'"),
                'gifts'      => (int) Database::scalar("SELECT COUNT(*) FROM visitor_responses WHERE type = 'gift' AND status = 'unread'"),
                'complaints' => (int) Database::scalar("SELECT COUNT(*) FROM visitor_responses WHERE type = 'complaint' AND status = 'unread'"),
            ];
        } catch (Throwable $e) {
            error_log('[dashboard] stats unavailable: ' . $e->getMessage());
            return array_fill_keys(['profiles', 'published', 'visitors', 'requests', 'gifts', 'complaints'], 0);
        }
    }
}
