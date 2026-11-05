<?php
/**
 * Admin — profile create/edit form. Data: $profile, $access, $timezones, $flash
 */

$isEdit = is_array($profile) && isset($profile['id']);
$p = static fn(string $key, string $default = ''): string => (string) ($profile[$key] ?? $default);
$birthdayValue = $isEdit ? str_replace(' ', 'T', (string) $profile['birthday_at']) : '';
$publishValue  = $isEdit ? str_replace(' ', 'T', (string) ($profile['publish_at'] ?? '')) : '';
?>
<form class="stack-form" method="post" action="<?= e(url($isEdit ? '/admin/profiles/' . $profile['id'] . '/update' : '/admin/profiles')) ?>">
    <?= csrf_field() ?>

    <div class="page-head">
        <div>
            <h1 class="welcome-title"><?= $isEdit ? 'Edit profile' : 'New birthday profile' ?> 🎂</h1>
            <p class="welcome-sub">One link, one password, one pure-cats movie.</p>
        </div>
        <div class="head-actions">
            <a class="btn-secondary" href="<?= e(url('/admin/profiles')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back</a>
            <?php if ($isEdit): ?>
                <a class="btn-secondary" href="<?= e(url('/admin/profiles/' . $profile['id'] . '/content')) ?>"><i class="fa-solid fa-feather" aria-hidden="true"></i> Content</a>
                <a class="btn-secondary" href="<?= e(url('/admin/profiles/' . $profile['id'] . '/media')) ?>"><i class="fa-solid fa-images" aria-hidden="true"></i> Media</a>
                <a class="btn-secondary" target="_blank" href="<?= e(url('/b/' . $profile['slug'])) ?>"><i class="fa-solid fa-eye" aria-hidden="true"></i> Preview</a>
            <?php endif; ?>
        </div>
    </div>

    <section class="glass-card form-card">
        <h2 class="card-title"><i class="fa-solid fa-id-card" aria-hidden="true"></i> Basics</h2>

        <div class="grid-2">
            <div class="field">
                <label for="display_name">Person's name</label>
                <div class="input-wrap">
                    <input type="text" id="display_name" name="display_name" required maxlength="120"
                           value="<?= e($p('display_name')) ?>" placeholder="Riya">
                </div>
            </div>
            <div class="field">
                <label for="slug">Link slug — /b/&lt;slug&gt;</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-link input-icon" aria-hidden="true"></i>
                    <input type="text" id="slug" name="slug" maxlength="100"
                           value="<?= e($p('slug')) ?>" placeholder="riya-birthday">
                </div>
            </div>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="birthday_at">Birthday date &amp; time</label>
                <input type="datetime-local" id="birthday_at" name="birthday_at" required value="<?= e($birthdayValue) ?>">
            </div>
            <div class="field">
                <label for="timezone">Timezone</label>
                <select id="timezone" name="timezone">
                    <?php foreach ($timezones as $tz): ?>
                        <option value="<?= e($tz) ?>" <?= $p('timezone', 'UTC') === $tz ? 'selected' : '' ?>><?= e($tz) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </section>

    <section class="glass-card form-card">
        <h2 class="card-title"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> Countdown</h2>

        <label class="switch-row"><input type="checkbox" name="block_before_birthday" <?= $p('block_before_birthday') === '1' ? 'checked' : '' ?>><span>Block visitors before the birthday (show the countdown page)</span></label>
        <label class="switch-row"><input type="checkbox" name="allow_early_peek" <?= $p('allow_early_peek') === '1' ? 'checked' : '' ?>><span>Allow an early peek with a countdown banner</span></label>

        <div class="grid-2">
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php foreach (['draft', 'scheduled', 'published', 'paused'] as $st): ?>
                        <option value="<?= $st ?>" <?= $p('status', 'draft') === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="help">"Scheduled" auto-publishes at "publish at" (no cron needed — checked live).</small>
            </div>
            <div class="field">
                <label for="publish_at">Publish at (for Scheduled)</label>
                <input type="datetime-local" id="publish_at" name="publish_at" value="<?= e($publishValue) ?>">
            </div>
        </div>
        <label class="switch-row"><input type="checkbox" name="allow_share" <?= $p('allow_share') === '1' ? 'checked' : '' ?>><span>Allow the finale "Share" button to copy the link</span></label>
    </section>

    <section class="glass-card form-card">
        <h2 class="card-title"><i class="fa-solid fa-lock" aria-hidden="true"></i> Password &amp; access window</h2>

        <div class="grid-2">
            <div class="field">
                <label for="password">Profile password <?= $isEdit ? '(leave empty to keep)' : '' ?></label>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
                    <input type="password" id="password" name="password" <?= $isEdit ? '' : 'required' ?> minlength="4" placeholder="<?= $isEdit ? '•••••• (unchanged)' : 'min 4 chars' ?>">
                </div>
            </div>
            <div class="field">
                <label for="access_hours">Access window (hours)</label>
                <input type="number" id="access_hours" name="access_hours" min="1" max="168"
                       value="<?= e($isEdit ? (string) ($access['access_hours'] ?? 24) : '24') ?>">
            </div>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="max_reentry_uses">Re-entry code uses (1 = one-time)</label>
                <input type="number" id="max_reentry_uses" name="max_reentry_uses" min="1" max="20"
                       value="<?= e($isEdit ? (string) ($access['max_reentry_uses'] ?? 1) : '1') ?>">
            </div>
            <div class="field">
                <label for="reentry_policy">Re-entry policy</label>
                <select id="reentry_policy" name="reentry_policy">
                    <option value="approve_required" <?= ($access['reentry_policy'] ?? 'approve_required') === 'approve_required' ? 'selected' : '' ?>>Admin approval required</option>
                    <option value="open" <?= ($access['reentry_policy'] ?? '') === 'open' ? 'selected' : '' ?>>Open (anyone may request)</option>
                </select>
            </div>
        </div>
        <p class="soon-note">At the end of the window visitors see <b>“Request access again”</b> and their request lands in the Requests tab.</p>
    </section>

    <button type="submit" class="btn-primary btn-block"><span class="btn-label"><?= $isEdit ? 'Save changes ✔' : 'Create profile 🎉' ?></span></button>
</form>
