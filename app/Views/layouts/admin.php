<?php
/**
 * Admin layout (sidebar + topbar). Data: $title, $admin, $content, $flash.
 */

use App\Core\Database;

$admin = $admin ?? [];
$adminName = (string) ($admin['display_name'] ?? 'Admin');
$flash     = $flash ?? null;

$appName = (string) (\App\Models\Settings::get('app_name', 'Birthday Surprise') ?? 'Birthday Surprise');
try {
    $pendingRequests = (int) (Database::scalar("SELECT COUNT(*) FROM access_requests WHERE status = 'pending'") ?? 0);
    $unreadInbox     = (int) (Database::scalar("SELECT COUNT(*) FROM visitor_responses WHERE status = 'unread'") ?? 0);
} catch (Throwable) {
    $pendingRequests = 0;
    $unreadInbox     = 0;
}
$brandParts = preg_split('/\s+/', trim($appName)) ?: ['Birthday', 'Surprise'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title ?? ('Admin · ' . $appName)) ?></title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🐾</text></svg>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>?v=10">
</head>
<body class="admin-shell"
      data-flash-type="<?= e($flash['type'] ?? '') ?>"
      data-flash-message="<?= e($flash['message'] ?? '') ?>">

    <aside class="sidebar">
        <div class="brand">
            <span class="brand-logo"><i class="fa-solid fa-paw" aria-hidden="true"></i></span>
            <span class="brand-name"><?= count($brandParts) > 1 ? e($brandParts[0]) . '<br>' . e(implode(' ', array_slice($brandParts, 1))) : e($brandParts[0]) ?></span>
        </div>

        <nav class="side-nav">
            <a class="nav-item <?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= e(url('/admin')) ?>">
                <i class="fa-solid fa-gauge-high" aria-hidden="true"></i><span>Dashboard</span></a>
            <a class="nav-item <?= ($active ?? '') === 'profiles' ? 'active' : '' ?>" href="<?= e(url('/admin/profiles')) ?>">
                <i class="fa-solid fa-cake-candles" aria-hidden="true"></i><span>Profiles</span></a>
            <a class="nav-item <?= ($active ?? '') === 'inbox' ? 'active' : '' ?>" href="<?= e(url('/admin/inbox')) ?>">
                <i class="fa-solid fa-envelope" aria-hidden="true"></i><span>Inbox</span>
                <?php if ($unreadInbox > 0): ?><em class="pill-badge warn"><?= $unreadInbox > 99 ? '99+' : $unreadInbox ?></em><?php endif; ?></a>
            <a class="nav-item <?= ($active ?? '') === 'requests' ? 'active' : '' ?>" href="<?= e(url('/admin/requests')) ?>">
                <i class="fa-solid fa-key" aria-hidden="true"></i><span>Requests</span>
                <?php if ($pendingRequests > 0): ?><em class="pill-badge warn"><?= $pendingRequests > 99 ? '99+' : $pendingRequests ?></em><?php endif; ?></a>
            <a class="nav-item <?= ($active ?? '') === 'analytics' ? 'active' : '' ?>" href="<?= e(url('/admin/analytics')) ?>">
                <i class="fa-solid fa-chart-line" aria-hidden="true"></i><span>Analytics</span></a>
            <a class="nav-item <?= ($active ?? '') === 'settings' ? 'active' : '' ?>" href="<?= e(url('/admin/settings')) ?>">
                <i class="fa-solid fa-gear" aria-hidden="true"></i><span>Settings</span></a>
        </nav>

        <div class="side-foot">
            <form method="post" action="<?= e(url('/admin/logout')) ?>" class="logout-form">
                <?= csrf_field() ?>
                <button type="submit" class="logout-link">
                    <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i><span>Sign out</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="main-wrap">
        <header class="topbar">
            <div class="topbar-search">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input type="text" placeholder="Search profiles, visitors, logs…" disabled title="Coming soon">
            </div>
            <div class="topbar-user">
                <span class="user-chip"><?= e(strtoupper(substr($adminName, 0, 1))) ?></span>
                <span class="user-name"><?= e($adminName) ?></span>
            </div>
        </header>

        <main class="content">
            <?= $content /* trusted internal template */ ?>
        </main>

        <footer class="admin-foot"><?= e($appName) ?> · admin area</footer>
    </div>

    <nav class="tabbar" aria-label="Mobile navigation">
        <a class="tab-item <?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= e(url('/admin')) ?>"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i>Home</a>
        <a class="tab-item <?= ($active ?? '') === 'profiles' ? 'active' : '' ?>" href="<?= e(url('/admin/profiles')) ?>"><i class="fa-solid fa-cake-candles" aria-hidden="true"></i>Profiles</a>
        <a class="tab-item <?= ($active ?? '') === 'inbox' ? 'active' : '' ?>" href="<?= e(url('/admin/inbox')) ?>"><i class="fa-solid fa-envelope" aria-hidden="true"></i>Inbox</a>
        <a class="tab-item <?= ($active ?? '') === 'requests' ? 'active' : '' ?>" href="<?= e(url('/admin/requests')) ?>"><i class="fa-solid fa-key" aria-hidden="true"></i>Requests</a>
        <a class="tab-item <?= ($active ?? '') === 'analytics' ? 'active' : '' ?>" href="<?= e(url('/admin/analytics')) ?>"><i class="fa-solid fa-chart-line" aria-hidden="true"></i>Stats</a>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
    <script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>
