<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;

final class AuthMiddleware
{
    /**
     * Run a route middleware strap; returns true when it already answered.
     *
     * Straps:
     *   'auth'  — admin session required (redirect, or 401 JSON for /api)
     *   'guest' — redirect signed-in admins away from login pages
     */
    public static function handle(string $strap, bool $api = false): bool
    {
        switch ($strap) {
            case 'auth':
                if (Auth::check()) {
                    return false;
                }
                if ($api) {
                    json_out(['message' => 'Unauthenticated.'], 401);
                }
                flash('error', 'Please sign in first 🐱');
                redirect(url('/admin/login'));
                return true;

            case 'guest':
                if (!Auth::check()) {
                    return false;
                }
                redirect(url('/admin'));
                return true;

            default:
                return false;
        }
    }
}
