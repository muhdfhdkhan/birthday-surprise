<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Models\Profile;
use App\Models\VisitorResponse;

final class InboxController
{
    private const TYPES   = ['gift', 'complaint', 'reaction'];
    private const STATUSES = ['unread', 'read', 'archived'];

    /** GET /admin/inbox — letters the visitors sent (gifts, complaints, reactions). */
    public function index(): void
    {
        $filters = [
            'profile' => max(0, min(PHP_INT_MAX, (int) ($_GET['profile'] ?? 0))),
            'type'    => VisitorResponse::cleanFilter($_GET['type'] ?? '', self::TYPES),
            'status'  => VisitorResponse::cleanFilter($_GET['status'] ?? '', self::STATUSES),
        ];

        view('admin/inbox', [
            'title'     => 'Inbox · Birthday Surprise',
            'active'    => 'inbox',
            'responses' => VisitorResponse::search($filters),
            'counts'    => VisitorResponse::countsByStatus(),
            'profiles'  => Profile::all(),
            'filters'   => $filters,
            'flash'     => pull_flash(),
        ], 'layouts/admin');
    }

    /** POST /admin/inbox/{id}/status — mark read / unread / archived. */
    public function status(int $id): void
    {
        $back = url('/admin/inbox' . $this->backQuery());
        if (!check_csrf()) {
            redirect($back);
        }

        $status = VisitorResponse::cleanFilter($_POST['status'] ?? '', self::STATUSES);
        if ($status === '' || !VisitorResponse::setStatus($id, $status)) {
            flash('error', 'That letter is already gone 🐱');
        } else {
            \App\Models\Admin::audit(
                (int) (Auth::user()['id'] ?? 0),
                'inbox_status', 'visitor_response', $id, '→ ' . $status
            );
            $word = ['unread' => 'Marked unread', 'read' => 'Marked as read', 'archived' => 'Archived'][$status];
            flash('success', $word . ' 🐾');
        }
        redirect($back);
    }

    /** POST /admin/inbox/{id}/delete — permanent removal. */
    public function destroy(int $id): void
    {
        $back = url('/admin/inbox' . $this->backQuery());
        if (!check_csrf()) {
            redirect($back);
        }

        try {
            VisitorResponse::destroy($id);
            \App\Models\Admin::audit(
                (int) (Auth::user()['id'] ?? 0),
                'inbox_delete', 'visitor_response', $id
            );
            flash('success', 'Letter deleted ✔');
        } catch (\Throwable $e) {
            error_log('[inbox] ' . $e->getMessage());
            flash('error', 'Could not delete the letter 🐱');
        }
        redirect($back);
    }

    /** Keep the visitor's filters across action redirects. */
    private function backQuery(): string
    {
        $q = [];
        if (!empty($_POST['back_profile'])) { $q['profile'] = (int) $_POST['back_profile']; }
        if (!empty($_POST['back_type']))    { $q['type']    = (string) $_POST['back_type']; }
        if (!empty($_POST['back_status']))  { $q['status']  = (string) $_POST['back_status']; }
        if ($q === []) { return ''; }
        return '?' . http_build_query($q);
    }
}
