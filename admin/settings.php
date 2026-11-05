<?php
/**
 * Admin — global settings. Data: $settings, $defaults, $env, $flash
 */

$s = function (string $key) use ($settings, $defaults): string {
    $v = (string) ($settings[$key] ?? '');
    return $v !== '' ? $v : (string) ($defaults[$key] ?? '');
};
$maintenance = $s('maintenance_mode') === '1';
?>
<div class="page-head">
    <div>
        <h1 class="welcome-title">Settings ⚙️</h1>
        <p class="welcome-sub">Global knobs for the whole app — saved in one place, honoured everywhere.</p>
    </div>
    <a class="btn-secondary" href="<?= e(url('/admin')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Dashboard</a>
</div>

<section class="glass-card form-card">
    <h2 class="card-title"><i class="fa-solid fa-sliders" aria-hidden="true"></i> General</h2>
    <form method="post" action="<?= e(url('/admin/settings')) ?>" class="settings-grid">
        <?= csrf_field() ?>

        <div class="setting-row">
            <label for="f_app_name"><b>App name</b><small>Shown in the admin side and page titles.</small></label>
            <input id="f_app_name" type="text" name="app_name" maxlength="80" value="<?= e($s('app_name')) ?>" required>
        </div>

        <div class="setting-row">
            <label for="f_app_timezone"><b>App timezone</b><small>Fallback timezone where a profile has none.</small></label>
            <select id="f_app_timezone" name="app_timezone">
                <?php foreach (['UTC', 'Asia/Kolkata', 'Asia/Dhaka', 'Asia/Karachi', 'Asia/Kuala_Lumpur', 'Europe/London', 'Europe/Berlin', 'America/New_York', 'America/Chicago', 'America/Los_Angeles', 'Australia/Sydney'] as $tz): ?>
                <option value="<?= e($tz) ?>" <?= $s('app_timezone') === $tz ? 'selected' : '' ?>><?= e($tz) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="setting-row">
            <label for="f_visitor_note"><b>Visitor privacy note</b><small>Shown on the password gate (max 300 chars).</small></label>
            <textarea id="f_visitor_note" name="visitor_privacy_note" rows="2" maxlength="300"><?= e($s('visitor_privacy_note')) ?></textarea>
        </div>

        <div class="setting-row setting-toggle <?= $maintenance ? 'is-danger' : '' ?>">
            <label for="f_maintenance"><b>Maintenance mode</b><small><?= $maintenance ? '⚠ Visitor pages are currently showing the maintenance note!' : 'Hide every birthday page behind a friendly notice.' ?></small></label>
            <span class="switch">
                <input type="checkbox" id="f_maintenance" name="maintenance_mode" value="1" <?= $maintenance ? 'checked' : '' ?>>
                <i>off · on</i>
            </span>
        </div>

        <h2 class="card-title gap-top"><i class="fa-solid fa-shield-cat" aria-hidden="true"></i> Safety & storage</h2>

        <div class="setting-row">
            <label for="f_gate_window"><b>Approved-access window</b><small>Default hours an approved re-entry code stays valid (1–168).</small></label>
            <input id="f_gate_window" type="number" name="access_window_hours" min="1" max="168" value="<?= e($s('access_window_hours')) ?>">
        </div>

        <div class="setting-row">
            <label for="f_upload_mb"><b>Max media upload size</b><small>Megabytes per photo or audio file (1–10).</small></label>
            <input id="f_upload_mb" type="number" name="uploads_max_mb" min="1" max="10" value="<?= e($s('uploads_max_mb')) ?>">
        </div>

        <div class="setting-row">
            <label for="f_types"><b>Allowed image types</b><small>Comma-separated out of: jpg, jpeg, png, webp.</small></label>
            <input id="f_types" type="text" name="uploads_allowed_types" value="<?= e($s('uploads_allowed_types')) ?>">
        </div>

        <div class="setting-row">
            <label for="f_retention"><b>Log retention</b><small>Days to keep visit logs before cleanups (7–365).</small></label>
            <input id="f_retention" type="number" name="log_retention_days" min="7" max="365" value="<?= e($s('log_retention_days')) ?>">
        </div>

        <div class="head-actions save-row">
            <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save settings</button>
        </div>
    </form>
</section>

<section class="glass-card form-card">
    <h2 class="card-title"><i class="fa-solid fa-server" aria-hidden="true"></i> Environment</h2>
    <dl class="env-grid">
        <div><dt>PHP</dt><dd><?= e($env['php']) ?></dd></div>
        <div><dt>Database</dt><dd><?= e($env['db']) ?></dd></div>
        <div><dt>OS</dt><dd><?= e($env['server']) ?></dd></div>
        <div><dt>PHP upload limit</dt><dd><?= e($env['uploads']) ?> <span class="muted">(app cap applies on top)</span></dd></div>
    </dl>
</section>
