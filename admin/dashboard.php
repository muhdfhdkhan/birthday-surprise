<?php
/**
 * Admin dashboard (content only — rendered inside app/Views/layouts/admin.php).
 * Data: $admin, $stats, $flash
 */

$adminName = (string) ($admin['display_name'] ?? 'Admin');
?>
<div class="page-head">
    <div>
        <h1 class="welcome-title">Welcome, <?= e($adminName) ?> <span class="wave-hand" aria-hidden="true">👋</span></h1>
        <p class="welcome-sub">Everything is ready for the next birthday surprise 🎂</p>
    </div>
    <a class="btn-secondary" href="<?= e(url('/admin/profiles/new')) ?>">
        <i class="fa-solid fa-plus" aria-hidden="true"></i> New birthday profile
    </a>
</div>

<section class="stat-grid" id="statGrid" aria-label="Overview">
    <article class="stat-card card-tint-rose">
        <span class="stat-icon"><i class="fa-solid fa-cake-candles" aria-hidden="true"></i></span>
        <span class="stat-label">Birthday profiles</span>
        <span class="stat-value"><?= (int) ($stats['profiles'] ?? 0) ?></span>
        <span class="stat-note"><?= (int) ($stats['published'] ?? 0) ?> published</span>
    </article>

    <article class="stat-card card-tint-lavender">
        <span class="stat-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
        <span class="stat-label">Visitors</span>
        <span class="stat-value"><?= (int) ($stats['visitors'] ?? 0) ?></span>
        <span class="stat-note">unique so far</span>
    </article>

    <article class="stat-card card-tint-mint">
        <span class="stat-icon"><i class="fa-solid fa-gift" aria-hidden="true"></i></span>
        <span class="stat-label">Gift wishes</span>
        <span class="stat-value"><?= (int) ($stats['gifts'] ?? 0) ?></span>
        <span class="stat-note">unread</span>
    </article>

    <article class="stat-card card-tint-butter">
        <span class="stat-icon"><i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i></span>
        <span class="stat-label">Complaints</span>
        <span class="stat-value"><?= (int) ($stats['complaints'] ?? 0) ?></span>
        <span class="stat-note">unread</span>
    </article>
</section>

<section class="glass-card quick-card">
    <h2 class="card-title"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> Waiting for you</h2>
    <div class="queue-grid">
        <div class="queue-item"><span class="q-number"><?= (int) ($stats['requests'] ?? 0) ?></span> pending access requests</div>
        <div class="queue-item"><span class="q-number"><?= (int) ($stats['gifts'] ?? 0) ?></span> gift wishes to read</div>
        <div class="queue-item"><span class="q-number"><?= (int) ($stats['complaints'] ?? 0) ?></span> complaints to check</div>
    </div>
    <p class="soon-note">Profiles, inbox, activity logs and settings arrive in the next phase —
        the database, sessions and routes are already wired for them.</p>
</section>
