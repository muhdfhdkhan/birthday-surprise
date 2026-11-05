<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Models\Admin;
use App\Models\Content;
use App\Models\Profile;
use App\Models\Sorry;
use App\Models\Wish;

final class ContentController
{
    /** Editor for one profile (content + sorry + wishes + reply settings). */
    public function show(int $id): void
    {
        $profile = Profile::find($id);
        if ($profile === null) {
            redirect(url('/admin/profiles'));
        }
        $content = Content::get($id);
        $sections = array_flip(explode(',', (string) ($content['sections_enabled'] ?? '')));

        view('admin/content', [
            'title'    => 'Content: ' . $profile['display_name'],
            'active'   => 'profiles',
            'profile'  => $profile,
            'content'  => $content,
            'sections' => $sections,
            'sorry'    => Sorry::forProfile($id),
            'wishes'   => Wish::forProfile($id),
            'flash'    => pull_flash(),
        ], 'layouts/admin');
    }

    /** Save the big content card (texts + toggles + reply settings). */
    public function save(int $id): void
    {
        if (!check_csrf() || Profile::find($id) === null) {
            redirect(url('/admin/profiles'));
        }

        $tr = static fn(string $key, int $max): string => trim((string) ($_POST[$key] ?? '')) !== ''
            ? mb_substr((string) $_POST[$key], 0, $max)
            : (($key === 'letter_text') ? null : '');

        $sections = array_values(array_filter((array) ($_POST['sections'] ?? [])));
        Content::updateMeta(
            $id,
            $sections,
            (string) ($_POST['secret_unlock_rule'] ?? 'after_sorry'),
            isset($_POST['sorry_required']) ? 1 : 0
        );

        $clean = static function (?string $html, string $fallback = ''): string {
            $value = trim((string) $html);
            if ($value === '') {
                return $fallback;
            }
            // Allowlist sanitiser (brief: allowlist HTML sanitiser for the letter).
            $value = strip_tags($value, '<p><br><b><strong><i><em><u><ul><ol><li><h3><h4>');
            $value = preg_replace('#\son\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $value) ?? '';
            $value = preg_replace('#javascript\s*:#i', '', $value) ?? '';
            return $value;
        };

        Content::update($id, [
            'welcome_message'       => $clean($_POST['welcome_message'] ?? null, 'Meow! Something special is waiting for you… 🎁'),
            'letter_text'           => $clean($_POST['letter_text'] ?? null),
            'secret_message'        => $clean($_POST['secret_message'] ?? null),
            'final_message'         => $clean($_POST['final_message'] ?? null),
            'post_birthday_message' => $clean($_POST['post_birthday_message'] ?? null),
            'gift_categories'       => mb_substr(trim((string) ($_POST['gift_categories'] ?? '')), 0, 500),
            'complaint_placeholder' => mb_substr(trim((string) ($_POST['complaint_placeholder'] ?? '')), 0, 255),
        ]);

        Admin::audit((int) (Auth::user()['id'] ?? 0), 'content_updated', 'profile', $id);
        flash('success', 'Content saved ✔');
        redirect(url('/admin/profiles/' . $id . '/content'));
    }

    // ---------- sorry messages ----------

    public function sorryAdd(int $id): void
    {
        if (check_csrf() && Profile::find($id) !== null) {
            $body = trim((string) ($_POST['body'] ?? ''));
            if ($body !== '') {
                Sorry::add($id, $body, (string) ($_POST['cat_action'] ?? 'begging'));
            }
        }
        redirect(url('/admin/profiles/' . $id . '/content'));
    }

    public function sorryUpdate(int $id, int $mid): void
    {
        if (check_csrf() && Profile::find($id) !== null) {
            Sorry::update(
                $id, $mid,
                trim((string) ($_POST['body'] ?? '')),
                (string) ($_POST['cat_action'] ?? 'begging'),
                mb_substr(trim((string) ($_POST['forgive_label'] ?? '')), 0, 80),
                mb_substr(trim((string) ($_POST['angry_label'] ?? '')), 0, 80)
            );
            flash('success', 'Apology updated ✔');
        }
        redirect(url('/admin/profiles/' . $id . '/content'));
    }

    public function sorryDelete(int $id, int $mid): void
    {
        if (check_csrf()) {
            Sorry::delete($id, $mid);
        }
        redirect(url('/admin/profiles/' . $id . '/content'));
    }

    public function sorryMove(int $id, int $mid): void
    {
        if (check_csrf()) {
            Sorry::move($id, $mid, (string) ($_POST['direction'] ?? 'down') === 'up' ? 'up' : 'down');
        }
        redirect(url('/admin/profiles/' . $id . '/content'));
    }

    // ---------- wishes ----------

    public function wishAdd(int $id): void
    {
        if (check_csrf() && Profile::find($id) !== null) {
            $body = trim((string) ($_POST['body'] ?? ''));
            if ($body !== '') {
                Wish::add($id, $body, (string) ($_POST['cat_action'] ?? 'dancing'));
            }
        }
        redirect(url('/admin/profiles/' . $id . '/content'));
    }

    public function wishUpdate(int $id, int $wid): void
    {
        if (check_csrf() && Profile::find($id) !== null) {
            Wish::update(
                $id, $wid,
                trim((string) ($_POST['body'] ?? '')),
                (string) ($_POST['cat_action'] ?? 'dancing')
            );
            flash('success', 'Wish updated ✔');
        }
        redirect(url('/admin/profiles/' . $id . '/content'));
    }

    public function wishDelete(int $id, int $wid): void
    {
        if (check_csrf()) {
            Wish::delete($id, $wid);
        }
        redirect(url('/admin/profiles/' . $id . '/content'));
    }

    public function wishMove(int $id, int $wid): void
    {
        if (check_csrf()) {
            Wish::move($id, $wid, (string) ($_POST['direction'] ?? 'down') === 'up' ? 'up' : 'down');
        }
        redirect(url('/admin/profiles/' . $id . '/content'));
    }
}
