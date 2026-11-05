<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\Admin;
use App\Services\RateLimiter;

final class AuthController
{
    /** GET / — send visitors where they belong. */
    public function home(): void
    {
        redirect(Auth::check() ? url('/admin') : url('/admin/login'));
    }

    /** GET /admin/login */
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect(url('/admin'));
        }

        view('admin/login', [
            'title' => 'Admin Login · Birthday Surprise',
            'flash' => pull_flash(),
        ]);
    }

    /** POST /admin/login */
    public function login(): void
    {
        if (!Csrf::check($_POST['_token'] ?? null)) {
            flash('error', 'Your session expired — please try again 🐾');
            redirect(url('/admin/login'));
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $ip       = client_ip();

        // Brute-force protection: 5 tries per IP + username per window.
        $maxAttempts = (int) config('security.login_max_attempts', 5);
        $window      = (int) config('security.login_window_minutes', 15);
        $key         = 'admin_login:' . $ip . ':' . strtolower($username);

        $limit = RateLimiter::status($key, $maxAttempts, $window);
        if ($limit['blocked']) {
            $mins = (int) ceil($limit['retry_in'] / 60);
            flash('error', "Too many attempts — the admin cat is napping 😴 Try again in {$mins} min.");
            redirect(url('/admin/login'));
        }

        $admin = $username !== '' ? Admin::findByUsername($username) : null;
        if ($admin === null) {
            password_verify($password, Admin::DUMMY_HASH); // equalise timing
        }

        $valid = is_array($admin)
            && (int) $admin['is_active'] === 1
            && password_verify($password, $admin['password_hash']);

        if (!$valid) {
            RateLimiter::hit($key, $window);
            Admin::audit(null, 'login_failed', 'admin', null, 'username=' . $username . ', ip=' . $ip);
            flash('error', 'Wrong username or password 🐱 Try again.');
            redirect(url('/admin/login'));
        }

        // Success: clear the limiter, secure the session, greet the admin.
        RateLimiter::reset($key);
        Auth::login($admin);
        Admin::markLogin((int) $admin['id'], $ip);
        Admin::audit((int) $admin['id'], 'login_success', 'admin', (int) $admin['id'], 'ip=' . $ip);

        flash('success', 'Welcome, ' . (string) ($admin['display_name'] ?? 'Admin') . '! 🎉');
        redirect(url('/admin'));
    }

    /** POST /admin/logout */
    public function logout(): void
    {
        if (Csrf::check($_POST['_token'] ?? null)) {
            $user = Auth::user();
            Auth::logout();
            if ($user !== null) {
                Admin::audit((int) $user['id'], 'logout', 'admin', (int) $user['id'], 'ip=' . client_ip());
            }
            flash('success', 'Signed out safely. Bye! 👋');
        } else {
            flash('error', 'Your session expired — please sign in again 🐾');
        }
        redirect(url('/admin/login'));
    }
}
