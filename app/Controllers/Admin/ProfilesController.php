<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Models\Admin;
use App\Models\Profile;

final class ProfilesController
{
    private string $error = '';


    public const TIMEZONES = [
        'UTC', 'Asia/Kolkata', 'Asia/Dubai', 'Asia/Singapore',
        'Europe/London', 'Europe/Berlin', 'Europe/Moscow',
        'America/New_York', 'America/Chicago', 'America/Los_Angeles',
        'Australia/Sydney', 'Pacific/Auckland',
    ];

    public function index(): void
    {
        $profiles = Profile::all();
        view('admin/profiles', [
            'title'    => 'Birthday Profiles',
            'active'   => 'profiles',
            'profiles' => $profiles,
            'flash'    => pull_flash(),
        ], 'layouts/admin');
    }

    public function create(): void
    {
        view('admin/profile_form', [
            'title'     => 'New birthday profile',
            'active'    => 'profiles',
            'profile'   => null,
            'access'    => null,
            'timezones' => self::TIMEZONES,
            'flash'     => pull_flash(),
        ], 'layouts/admin');
    }

    public function store(): void
    {
        if (!check_csrf()) {
            redirect(url('/admin/profiles'));
        }

        $data = $this->collect();

        if (!is_array($data)) {
            flash('error', $this->error ?? 'Invalid input 🐱');
            redirect(url('/admin/profiles/new'));
        }

        [$slug] = $this->slugFrom($data['slug'] ?? '', $data['display_name']);
        $data['slug'] = $slug;

        $id = Profile::create($data, (int) (Auth::user()['id'] ?? 0));
        if ($id === null) {
            flash('error', 'Could not save the profile — is the slug unique? 🐾');
            redirect(url('/admin/profiles/new'));
        }

        flash('success', 'Profile created 🎉 Visit link: ' . url('/b/' . $data['slug']));
        redirect(url('/admin/profiles/' . $id . '/edit'));
    }

    public function edit(int $id): void
    {
        $profile = Profile::find($id);
        if ($profile === null) {
            flash('error', 'Profile not found 🐱');
            redirect(url('/admin/profiles'));
        }
        view('admin/profile_form', [
            'title'     => 'Edit: ' . $profile['display_name'],
            'active'    => 'profiles',
            'profile'   => $profile,
            'access'    => Profile::accessSetting($id),
            'timezones' => self::TIMEZONES,
            'flash'     => pull_flash(),
        ], 'layouts/admin');
    }

    public function update(int $id): void
    {
        if (!check_csrf()) {
            redirect(url('/admin/profiles'));
        }
        $profile = Profile::find($id);
        if ($profile === null) {
            redirect(url('/admin/profiles'));
        }

        $data = $this->collect();
        if (!is_array($data)) {
            flash('error', $this->error ?? 'Invalid input 🐱');
            redirect(url('/admin/profiles/' . $id . '/edit'));
        }

        [$slug] = $this->slugFrom($data['slug'] ?? '', $data['display_name'], $id);
        $data['slug'] = $slug;

        Profile::update($id, $data);
        Admin::audit((int) (Auth::user()['id'] ?? 0), 'profile_updated', 'profile', $id);

        flash('success', 'Profile saved ✔');
        redirect(url('/admin/profiles/' . $id . '/edit'));
    }

    public function publish(int $id): void
    {
        $this->transition($id, 'published');
    }

    public function unpublish(int $id): void
    {
        $this->transition($id, 'paused');
    }

    public function archive(int $id): void
    {
        $this->transition($id, 'archived');
    }

    public function restore(int $id): void
    {
        Profile::restore($id);
        Admin::audit((int) (Auth::user()['id'] ?? 0), 'profile_restored', 'profile', $id);
        flash('success', 'Profile restored to draft 🐾');
        redirect(url('/admin/profiles'));
    }

    public function destroy(int $id): void
    {
        if (!check_csrf()) {
            redirect(url('/admin/profiles'));
        }
        Profile::destroy($id);
        Admin::audit((int) (Auth::user()['id'] ?? 0), 'profile_deleted', 'profile', $id);
        flash('success', 'Profile deleted 🧹');
        redirect(url('/admin/profiles'));
    }

    // ---------- helpers ----------

    private function transition(int $id, string $status): void
    {
        if (!check_csrf()) {
            redirect(url('/admin/profiles'));
        }
        Profile::setStatus($id, $status, (int) (Auth::user()['id'] ?? 0));
        $label = ['published' => 'Published 🎉', 'paused' => 'Unpublished (paused) 😴', 'archived' => 'Archived 📦'][$status] ?? $status;
        flash('success', 'Profile ' . $label);
        redirect(url('/admin/profiles'));
    }

    /** Collect + validate the form. Returns array or null (error in $this->error). */
    private function collect(): ?array
    {
        $this->error = '';
        $post = $_POST;

        $name = trim((string) ($post['display_name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 120) {
            $this->error = 'A display name (max 120 chars) is required 😺';
            return null;
        }

        $birthday = trim((string) ($post['birthday_at'] ?? ''));
        $ts = \DateTime::createFromFormat('Y-m-d\TH:i', $birthday);
        if ($ts === false) {
            $this->error = 'A valid birthday date & time is needed 🎂';
            return null;
        }

        $tz = (string) ($post['timezone'] ?? 'UTC');
        if (!in_array($tz, self::TIMEZONES, true)) {
            $tz = 'UTC';
        }

        $status = (string) ($post['status'] ?? 'draft');
        if (!in_array($status, Profile::STATUSES, true)) {
            $status = 'draft';
        }

        $publishAt = null;
        if ($status === 'scheduled') {
            $pTs = \DateTime::createFromFormat('Y-m-d\TH:i', trim((string) ($post['publish_at'] ?? '')));
            if ($pTs === false) {
                $this->error = 'Scheduled profiles need a "publish at" time ⏰';
                return null;
            }
            $publishAt = $pTs->format('Y-m-d H:i:s');
        }

        $password = (string) ($post['password'] ?? '');
        $editing  = (int) ($post['id'] ?? 0) > 0;
        if (!$editing || $password !== '') {
            if (mb_strlen($password) < 4) {
                $this->error = 'Password needs at least 4 characters 🔒';
                return null;
            }
        }

        $hours = max(1, min(168, (int) ($post['access_hours'] ?? 24)));
        $uses  = max(1, min(20, (int) ($post['max_reentry_uses'] ?? 1)));
        $policy = ($post['reentry_policy'] ?? 'approve_required') === 'open' ? 'open' : 'approve_required';

        return [
            'display_name'         => $name,
            'slug'                 => trim((string) ($post['slug'] ?? '')),
            'birthday_at'          => $ts->format('Y-m-d H:i:s'),
            'timezone'             => $tz,
            'status'               => $status,
            'publish_at'           => $publishAt,
            'block_before_birthday' => isset($post['block_before_birthday']) ? 1 : 0,
            'allow_early_peek'     => isset($post['allow_early_peek']) ? 1 : 0,
            'allow_share'          => isset($post['allow_share']) ? 1 : 0,
            'password'             => $password,
            'access_hours'         => $hours,
            'max_reentry_uses'     => $uses,
            'reentry_policy'       => $policy,
        ];
    }

    /** Normalise a desired slug or generate one. Returns [slug]. */
    private function slugFrom(string $desired, string $name, int $ignoreId = 0): array
    {
        if ($desired !== '') {
            $candidate = trim(preg_replace('/[^a-z0-9]+/i', '-', $desired) ?? '', '-');
            if ($candidate === '' || Profile::slugTaken($candidate, $ignoreId)) {
                return [Profile::uniqueSlug($desired !== '' ? $desired : $name, $ignoreId)];
            }
            return [$candidate];
        }
        return [Profile::uniqueSlug($name, $ignoreId)];
    }
}
