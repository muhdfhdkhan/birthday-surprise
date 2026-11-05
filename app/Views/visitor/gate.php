<?php
/**
 * Visitor gate — countdown / password / expired states.
 * Data: $profile, $slug, $state, $visitFlash, $targetIso, $privacy
 */

$slug   = (string) $slug;
$state  = (string) $state;
$profileName = (string) $profile['display_name'];
$face = ['countdown' => '😴', 'expired' => '🕰️', 'password' => '🐱'][$state] ?? '🐱';
?>
<section class="story-card glass-card gate-card <?= $state === 'countdown' ? 'is-countdown' : '' ?>" data-gate="<?= e($state) ?>">
    <div class="cat-face" id="gateCat" aria-hidden="true"><?= $face ?></div>

    <?php if ($state === 'countdown'): ?>
        <h1 class="gate-title">Almost time…</h1>
        <p class="gate-sub">A little surprise is napping until the big day for <?= e($profileName) ?> 🎂</p>
        <div class="count-tiles" id="countTiles" data-target="<?= e($targetIso ?? '') ?>" aria-live="off">
            <div class="ct"><b id="ctD">–</b><span>days</span></div>
            <div class="ct"><b id="ctH">–</b><span>hours</span></div>
            <div class="ct"><b id="ctM">–</b><span>mins</span></div>
            <div class="ct"><b id="ctS">–</b><span>secs</span></div>
        </div>
        <p class="gate-note">Shh… it's a surprise 🐾</p>

    <?php elseif ($state === 'expired'): ?>
        <h1 class="gate-title">Your time is up 🕰️</h1>
        <p class="gate-sub">The magic window for this surprise closed. Ask for a new one, or use your secret code.</p>

        <form method="post" action="<?= e(url('/b/' . $slug . '/access-request')) ?>" class="gate-form">
            <?= csrf_field() ?>
            <div class="field">
                <label for="note">Little note for the admin (optional)</label>
                <textarea id="note" name="note" rows="2" maxlength="500" placeholder="It's me again… 🥺"></textarea>
            </div>
            <button type="submit" class="btn-primary btn-block"><span class="btn-label">Request access again 🥺</span></button>
        </form>

        <div class="divider"><span>or have a ticket?</span></div>

        <form method="post" action="<?= e(url('/b/' . $slug . '/redeem')) ?>" class="gate-form">
            <?= csrf_field() ?>
            <div class="field">
                <label for="code">Secret code</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-ticket input-icon" aria-hidden="true"></i>
                    <input type="text" id="code" name="code" placeholder="XXXX-XXXX-XXXX-XXXX" autocomplete="off" maxlength="19">
                </div>
            </div>
            <button type="submit" class="btn-secondary btn-block"><span class="btn-label">Use my code 🎫</span></button>
        </form>

    <?php else: ?>
        <h1 class="gate-title">Hello! A surprise is waiting 🎁</h1>
        <p class="gate-sub">This page is locked — only <?= e($profileName) ?> may open it. Sound like you?</p>

        <form method="post" action="<?= e(url('/b/' . $slug . '/unlock')) ?>" class="gate-form" id="unlockForm">
            <?= csrf_field() ?>
            <div class="field">
                <label for="password">Secret password</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
                    <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="psst…">
                    <button type="button" class="pw-toggle" id="pwToggle" aria-label="Show password"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
                </div>
            </div>
            <button type="submit" class="btn-primary btn-block" id="unlockBtn">
                <span class="btn-label">Open your surprise 🎉</span>
            </button>
        </form>

        <form method="post" action="<?= e(url('/b/' . $slug . '/redeem')) ?>" class="gate-form tiny-form">
            <?= csrf_field() ?>
            <input type="text" id="code" name="code" placeholder="Got a code? XXXX-…" autocomplete="off" maxlength="19" class="code-inline">
            <button type="submit" class="btn-secondary">Use it 🎫</button>
        </form>
    <?php endif; ?>

    <p class="privacy-line"><i class="fa-solid fa-shield-heart" aria-hidden="true"></i> <?= e($privacy ?? '') ?></p>
</section>
