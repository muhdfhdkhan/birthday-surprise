<?php
/**
 * Admin — visitor analytics & activity logs (Phase 9).
 * Data: $chosen, $pid, $days, $profiles, $totals, $events, $daily, $topIps,
 *       $browsers, $devices, $recent, $reqRows, $flash
 */

$totals = $totals ?? [];
$fmtE = static function (string $e): string {
    $map = [
        'page_view'       => '👁 Page visit',
        'password_success'=> '🔓 Password OK',
        'password_fail'   => '🔒 Password fail',
        'access_request'  => '📩 Access request',
        'grant_redeem'    => '🎟 Code redeemed',
        'stage_view'      => '➡ Stage viewed',
        'forgive_click'   => '😿→😻 Forgive',
        'angry_click'     => '😾 Still angry',
        'candle_blown'    => '🕯 Candles blown',
        'photo_open'      => '📸 Photo opened',
        'music_play'      => '🎵 Music started',
        'music_pause'     => '🔇 Music paused',
        'letter_skip'     => '⏩ Letter skipped',
        'secret_reveal'   => '💧 Secret revealed',
        'gift_submit'     => '🎁 Gift wish',
        'complaint_submit'=> '💌 Complaint sent',
        'reaction_click'  => '💖 Reaction',
        'replay_click'    => '🔁 Replay',
        'share_click'     => '📤 Share',
    ];
    return $map[$e] ?? str_replace('_', ' ', $e);
};

/** Scale helper for the daily bar chart. */
$maxDaily = 0;
foreach ($daily as $d) {
    $maxDaily = max($maxDaily, (int) $d['c']);
}
?>
<div class="page-head">
    <div>
        <h1 class="welcome-title">Visitor analytics 📊</h1>
        <p class="welcome-sub">Who came to the birthday party, and what they did there 🐾</p>
    </div>
    <form method="get" class="ana-filter" action="<?= e(url('/admin/analytics')) ?>">
        <select name="profile">
            <option value="0">All profiles</option>
            <?php foreach ($profiles as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= $pid === (int) $p['id'] ? 'selected' : '' ?>>
                <?= e((string) $p['display_name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select name="days" title="Time window">
            <?php foreach ([7, 14, 30, 90] as $d): ?>
            <option value="<?= $d ?>" <?= $days === $d ? 'selected' : '' ?>>Last <?= $d ?> days</option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary"><i class="fa-solid fa-filter" aria-hidden="true"></i> Apply</button>
    </form>
</div>

<?php if ($chosen !== null): ?>
<p class="ana-focus">Showing activity for <b><?= e((string) $chosen['display_name']) ?></b> ·
    <a href="<?= e(url('/admin/analytics')) ?>">clear filter</a></p>
<?php endif; ?>

<!-- ---------- headline stats ---------- -->
<section class="stat-grid ana-grid" aria-label="Overview numbers">
    <article class="stat-card card-tint-lavender">
        <span class="stat-icon"><i class="fa-solid fa-eye" aria-hidden="true"></i></span>
        <span class="stat-label">Page visits</span>
        <span class="stat-value"><?= number_format((int) $totals['page_views']) ?></span>
        <span class="stat-note">last <?= (int) $days ?> days</span>
    </article>
    <article class="stat-card card-tint-rose">
        <span class="stat-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
        <span class="stat-label">Unique visitors</span>
        <span class="stat-value"><?= number_format((int) $totals['visitors']) ?></span>
        <span class="stat-note"><?= number_format((int) $totals['sessions']) ?> sessions</span>
    </article>
    <article class="stat-card card-tint-mint">
        <span class="stat-icon">
            <i class="fa-solid fa-<?= $totals['pw_fail'] > 0 ? 'lock' : 'unlock' ?>" aria-hidden="true"></i></span>
        <span class="stat-label">Password tries</span>
        <span class="stat-value"><?= number_format((int) ($totals['pw_success'] ?? 0) + (int) $totals['pw_fail']) ?></span>
        <span class="stat-note">
            <?= $totals['unlock_rate'] !== null ? (int) $totals['unlock_rate'] . '% success · ' : '' ?>
            <?= (int) $totals['pw_fail'] ?> failed</span>
    </article>
    <article class="stat-card card-tint-butter">
        <span class="stat-icon"><i class="fa-solid fa-gift" aria-hidden="true"></i></span>
        <span class="stat-label">Gifts / complaints</span>
        <span class="stat-value"><?= number_format((int) $totals['gifts']) ?> / <?= number_format((int) $totals['complaints']) ?></span>
        <span class="stat-note"><?= number_format((int) $totals['reactions']) ?> reactions</span>
    </article>
</section>

<!-- ---------- daily activity chart ---------- -->
<section class="glass-card">
    <h2 class="card-title"><i class="fa-solid fa-chart-column" aria-hidden="true"></i> Activity, last <?= (int) $days ?> days</h2>
    <?php if ($maxDaily === 0): ?>
        <p class="empty-inline">No activity logged in this window yet 🐱</p>
    <?php else: ?>
    <div class="ana-chart" role="img" aria-label="Events per day, bar chart">
        <?php foreach ($daily as $d): ?>
        <div class="ana-col" title="<?= e((string) $d['date']) ?>: <?= (int) $d['c'] ?> events">
            <span class="ana-bar" style="height: <?= $maxDaily > 0 ? (int) round(100 * (int) $d['c'] / $maxDaily) : 0 ?>%"></span>
            <span class="ana-day"><?= e((string) $d['label']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<div class="ana-split">
    <!-- ---------- event breakdown ---------- -->
    <section class="glass-card">
        <h2 class="card-title"><i class="fa-solid fa-list-check" aria-hidden="true"></i> What visitors did <span class="muted">(all time)</span></h2>
        <?php if (count($events) === 0): ?>
            <p class="empty-inline">Nothing logged yet — share a birthday link to see it fill up 🎉</p>
        <?php else: ?>
        <table class="ana-table">
            <thead><tr><th>Event</th><th class="num">Count</th><th>Last seen</th></tr></thead>
            <tbody>
            <?php foreach ($events as $ev): ?>
                <tr>
                    <td><?= e($fmtE((string) $ev['event'])) ?></td>
                    <td class="num"><b><?= number_format((int) $ev['c']) ?></b></td>
                    <td class="muted"><?= e((string) $ev['last_seen']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>

    <div>
        <!-- ---------- browsers & devices ---------- -->
        <?php if (count($browsers) || count($devices)): ?>
        <section class="glass-card ana-half">
            <h2 class="card-title"><i class="fa-solid fa-laptop" aria-hidden="true"></i> Where they came from</h2>
            <div class="ana-two">
                <div>
                    <h3 class="ana-sub">Browsers (page views)</h3>
                    <?php foreach ($browsers as $b): ?>
                    <div class="ana-meter">
                        <span><?= e((string) $b['label']) ?></span>
                        <b><?= number_format((int) $b['c']) ?></b>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div>
                    <h3 class="ana-sub">Devices (page views)</h3>
                    <?php foreach ($devices as $dv): ?>
                    <div class="ana-meter">
                        <span><?= e((string) $dv['label']) ?></span>
                        <b><?= number_format((int) $dv['c']) ?></b>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ---------- top IPs ---------- -->
        <section class="glass-card ana-half">
            <h2 class="card-title"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Most active IPs <span class="muted">(all time)</span></h2>
            <?php if (count($topIps) === 0): ?>
                <p class="empty-inline">No visitor IPs logged yet 🐾</p>
            <?php else: ?>
            <table class="ana-table">
                <thead><tr><th>IP</th><th>Device</th><th class="num">Events</th><th class="num">Sessions</th><th>Last seen</th></tr></thead>
                <tbody>
                <?php foreach ($topIps as $ip): ?>
                    <tr>
                        <td class="mono"><?= e((string) ($ip['ip'] ?? '—')) ?></td>
                        <td class="muted"><?= e((string) ($ip['os'] ?? '')) ?> <?= e((string) ($ip['browser'] ?? '')) ?>
                            <?= trim((string) ($ip['os'] ?? '') . (string) ($ip['browser'] ?? '')) === '' ? 'Unknown' : '' ?></td>
                        <td class="num"><b><?= number_format((int) $ip['c']) ?></b></td>
                        <td class="num"><?= (int) $ip['sessions'] ?></td>
                        <td class="muted"><?= e((string) $ip['last_seen']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </section>
    </div>
</div>

<!-- ---------- access requests ---------- -->
<section class="glass-card">
    <h2 class="card-title"><i class="fa-solid fa-key" aria-hidden="true"></i> Access request trail</h2>
    <?php if (count($reqRows) === 0): ?>
        <p class="empty-inline">No access requests logged 🐱</p>
    <?php else: ?>
    <table class="ana-table">
        <thead><tr><th>Profile</th><th>Status</th><th>Note</th><th>IP</th><th>When</th></tr></thead>
        <tbody>
        <?php foreach ($reqRows as $r): ?>
            <tr>
                <td><b><?= e((string) ($r['profile_name'] ?? 'Deleted')) ?></b></td>
                <td><span class="status-pill <?= $r['status'] === 'pending' ? 'pill-scheduled' : ($r['status'] === 'approved' ? 'pill-published' : 'pill-archived') ?>"><?= e((string) $r['status']) ?></span></td>
                <td class="muted"><?= e(mb_strimwidth((string) ($r['note'] ?? ''), 0, 60, '…')) ?></td>
                <td class="mono"><?= e((string) ($r['ip_address'] ?? '—')) ?></td>
                <td class="muted"><?= e((string) $r['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</section>

<!-- ---------- activity feed ---------- -->
<section class="glass-card">
    <h2 class="card-title"><i class="fa-solid fa-stream" aria-hidden="true"></i> Recent activity
        <span class="muted"><?= $pid > 0 ? '· filtered' : '· all profiles' ?></span></h2>
    <?php if (count($recent) === 0): ?>
        <p class="empty-inline">Log is empty — every visit, unlock and button press lands here 🐾</p>
    <?php else: ?>
    <div class="ana-table-wrap">
    <table class="ana-table">
        <thead><tr><th>When</th><th>Profile</th><th>Event</th><th>Stage</th><th>IP</th><th>Device</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $l): ?>
            <tr>
                <td class="muted"><?= e((string) $l['created_at']) ?></td>
                <td><b><?= e((string) ($l['profile_name'] ?? '—')) ?></b></td>
                <td><?= e($fmtE((string) $l['event'])) ?></td>
                <td class="muted"><?= e((string) ($l['stage'] ?? '—')) ?></td>
                <td class="mono"><?= e((string) ($l['ip_address'] ?? '—')) ?></td>
                <td class="muted"><?= e((string) $l['device']) ?> · <?= e((string) $l['os']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</section>
