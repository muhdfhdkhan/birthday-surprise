<?php
/**
 * Global helper functions (loaded by app/bootstrap.php before everything else).
 */

declare(strict_types=1);

/** Return the app config array, or a value by 'section.key' path. */
function config(?string $key = null, mixed $default = null): mixed
{
    global $config;
    static $cache = null;
    if ($cache === null) {
        $cache = is_array($config ?? null) ? $config : [];
    }
    if ($key === null) {
        return $cache;
    }
    $value = $cache;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

/** Escape for HTML output. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Base URL of the front-controller directory (works in a subfolder of htdocs). */
function base_url(): string
{
    static $base = null;
    if ($base === null) {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $base = rtrim(dirname($script), '/');
        if ($base === '/' || $base === '.') {
            $base = '';
        }
    }
    return $base;
}

/** Build an app URL: url('/admin/login') -> /birthday-surprise/public/admin/login */
function url(string $path = '/'): string
{
    return base_url() . '/' . ltrim($path, '/');
}

/** URL for an asset inside /public/assets. */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/** Redirect and stop. */
function redirect(string $to, int $code = 302): never
{
    if (!headers_sent()) {
        header('Location: ' . $to, true, $code);
    } else {
        echo '<script>location.replace(' . json_encode($to) . ');</script>';
    }
    exit;
}

/** Send JSON and stop. */
function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

/** Store a one-shot flash message ('success' | 'error'). */
function flash(string $type, string $message): void
{
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

/** Pull the flash message once. */
function pull_flash(): ?array
{
    if (isset($_SESSION['_flash'])) {
        $f = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return $f;
    }
    return null;
}

/** Best-effort client IP (can be spoofed behind proxies unless hardened later). */
function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

/** Per-slug flash for visitor pages (they never share an admin session). */
function flash_visit(string $slug, string $type, string $message): void
{
    $_SESSION['visit_flash'][$slug] = ['type' => $type, 'message' => $message];
}

/** Pull that visitor flash once. */
function pull_visit(string $slug): ?array
{
    if (isset($_SESSION['visit_flash'][$slug])) {
        $f = $_SESSION['visit_flash'][$slug];
        unset($_SESSION['visit_flash'][$slug]);
        return $f;
    }
    return null;
}

/** Render a template (optionally inside a layout). */
function view(string $template, array $data = [], ?string $layout = null): void
{
    App\Core\View::render($template, $data, $layout);
}

/** CSRF token for forms. */
function csrf_token(): string
{
    return App\Core\Csrf::token();
}

/** Hidden CSRF input field. */
function csrf_field(): string
{
    return App\Core\Csrf::field();
}

/** Validate the current POST against the session token; flash if bad. */
function check_csrf(): bool
{
    if (App\Core\Csrf::check($_POST['_token'] ?? null)) {
        return true;
    }
    flash('error', 'Your session expired — please try again 🐾');
    return false;
}
