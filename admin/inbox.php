<?php
/**
 * Admin — inbox: letters visitors sent (gifts / complaints / reactions).
 * Data: $responses, $counts, $profiles, $filters, $flash
 */

$typeMeta = [
    'gift'      => ['Gift wish',      'fa-gift',               'gift'],
    'complaint' => ['Letter to me',   'fa-envelope-open-text', 'letter'],
    'reaction'  => ['Reaction',       'fa-face-smile',         'reaction'],
];
$statusPill = [
    'unread'   => ['Unread',   'pill-scheduled'],
    'read'     => ['Read',     'pill-published'],
    'archived' => ['Archived', 'pill-archived'],
];
$typeTabs = ['' => 'All', 'gift' => 'Gift wish', 'complaint' => 'Letter to me', 'reaction' => 'Reaction'];

$filterProfile = (int) ($filters['profile'] ?? 0);
$filterType    = (string) ($filters['type'] ?? '');
$filterStatus  = (string) ($filters['status'] ?? '');

/** Build an inbox URL with two filters kept and one replaced. */
$inboxUrl = function (string $which, string $value) use ($filterProfile, $filterType, $filterStatus): string {
    $v = ['profile' => $filterProfile, 'type' => $filterType, 'status' => $filterStatus];
    $v[$which] = $value;
    $q = [];
    if ($v['profile'] > 0) { $q['profile'] = $v['profile']; }
    if ($v['type'] !== '') { $q['type'] = $v['type']; }
    if ($v['status'] !== '') { $q['status'] = $v['status']; }
    return url('/admin/inbox' . ($q === [] ? '' : '?' . http_build_query($q)));
};
?>
<div class="page-head">
    <div>
        <h1 class="welcome-title">Inbox 📬</h1>
        <p class="welcome-sub">Gift wishes, feelings and reactions your visitors sent through the story.</p>
    </div>
    <a class="btn-secondary" href="<?= e(url('/admin/profiles')) ?>"><i class="fa-solid fa-cake-candles" aria-hidden="true"></i> Profiles</a>
</div>

<section class="glass-card form-card">
    <div class="filter-row" role="toolbar" aria-label="Status filter">
        <span class="lbl-tiny">Status</span>
        <?php foreach (['' => 'All', 'unread' => 'Unread', 'read' => 'Read', 'archived' => 'Archived'] as $key => $label): ?>
        <a class="filter-chip <?= $filterStatus === $key ? 'is-on' : '' ?>" href="<?= e($inboxUrl('status', $key)) ?>">
            <?= e($label) ?><?= $key !== '' ? ' (' . (int) ($counts[$key] ?? 0) . ')' : '' ?>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="filter-row" role="toolbar" aria-label="Type filter">
        <span class="lbl-tiny">Type</span>
        <?php foreach ($typeTabs as $key => $label): ?>
        <a class="filter-chip <?= $filterType === $key ? 'is-on' : '' ?>" href="<?= e($inboxUrl('type', $key)) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>

    <form method="get" action="<?= e(url('/admin/inbox')) ?>" class="filter-row filter-profile">
        <span class="lbl-tiny">Profile</span>
        <select name="profile" onchange="this.form.submit()">
            <option value="0">All profiles</option>
            <?php foreach ($profiles as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= $filterProfile === (int) $p['id'] ? 'selected' : '' ?>>
                <?= e((string) $p['display_name']) ?><?= $p['status'] !== 'published' ? ' (' . e((string) $p['status']) . ')' : '' ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php if ($filterType !== ''): ?><input type="hidden" name="type" value="<?= e($filterType) ?>"><?php endif; ?>
        <?php if ($filterStatus !== ''): ?><input type="hidden" name="status" value="<?= e($filterStatus) ?>"><?php endif; ?>
        <noscript><button type="submit" class="btn-secondary">Show</button></noscript>
    </form>
</section>

<section class="glass-card form-card">
    <h2 class="card-title"><i class="fa-solid fa-envelope" aria-hidden="true"></i>
        Letters (<?= count($responses) ?><?= $filterType !== '' || $filterStatus !== '' || $filterProfile > 0 ? ' matching' : '' ?>)</h2>

    <?php if (count($responses) === 0): ?>
        <p class="empty-inline">Nothing here yet — the inbox is purr-fectly quiet 🐈</p>
    <?php else: ?>
        <?php foreach ($responses as $r): [$meta, $icon] = $typeMeta[$r['type']] ?? [ucfirst($r['type']), 'fa-envelope']; ?>
        <?php [$pillText, $pillClass] = $statusPill[$r['status']] ?? [$r['status'], 'pill-draft']; ?>
        <div class="list-row ib-row <?= $r['status'] === 'unread' ? 'is-unread' : '' ?>">
            <span class="ib-icon" aria-hidden="true"><i class="fa-solid <?= e($icon) ?>"></i></span>
            <div class="ib-body">
                <div class="ib-head">
                    <b><?= e((string) ($r['profile_name'] ?? 'Deleted profile')) ?></b>
                    <span class="status-pill <?= $pillClass ?>"><?= e($pillText) ?></span>
                    <span class="ib-kind"><?= e((string) $meta) ?></span>
                    <span class="muted"><?= e((string) $r['created_at']) ?></span>
                </div>
                <?php if ($r['type'] === 'reaction'): ?>
                    <p class="ib-text"><span class="reaction-badge"><?= e((string) ($r['reaction'] ?? '🙂')) ?></span> picked from the finale</p>
                <?php else: ?>
                    <?php if (!empty($r['category'])): ?><span class="ib-cat">🎁 <?= e((string) $r['category']) ?></span><?php endif; ?>
                    <p class="ib-text"><?= $r['body'] !== null && $r['body'] !== '' ? nl2br(e((string) $r['body'])) : '<span class="muted">(no text — just the tap)</span>' ?></p>
                <?php endif; ?>
                <p class="muted tiny-ip">from <?= e(mb_substr((string) ($r['visitor_uid'] ?? ''), 0, 8)) ?: 'anon' ?> · IP <?= e((string) ($r['ip_address'] ?? '—')) ?></p>
            </div>

            <div class="ib-actions">
                <?php if ($r['status'] !== 'read'): ?>
                <form method="post" action="<?= e(url('/admin/inbox/' . (int) $r['id'] . '/status')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="read">
                    <input type="hidden" name="back_profile" value="<?= $filterProfile ?>">
                    <input type="hidden" name="back_type" value="<?= e($filterType) ?>">
                    <input type="hidden" name="back_status" value="<?= e($filterStatus) ?>">
                    <button type="submit" class="act act-primary" title="Mark as read"><i class="fa-solid fa-envelope-open" aria-hidden="true"></i></button>
                </form>
                <?php endif; ?>
                <?php if ($r['status'] !== 'archived'): ?>
                <form method="post" action="<?= e(url('/admin/inbox/' . (int) $r['id'] . '/status')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="archived">
                    <input type="hidden" name="back_profile" value="<?= $filterProfile ?>">
                    <input type="hidden" name="back_type" value="<?= e($filterType) ?>">
                    <input type="hidden" name="back_status" value="<?= e($filterStatus) ?>">
                    <button type="submit" class="act" title="Archive"><i class="fa-solid fa-box-archive" aria-hidden="true"></i></button>
                </form>
                <?php else: ?>
                <form method="post" action="<?= e(url('/admin/inbox/' . (int) $r['id'] . '/status')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="read">
                    <input type="hidden" name="back_profile" value="<?= $filterProfile ?>">
                    <input type="hidden" name="back_type" value="<?= e($filterType) ?>">
                    <input type="hidden" name="back_status" value="<?= e($filterStatus) ?>">
                    <button type="submit" class="act" title="Restore to inbox"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i></button>
                </form>
                <?php endif; ?>
                <form method="post" action="<?= e(url('/admin/inbox/' . (int) $r['id'] . '/delete')) ?>"
                      onsubmit="return confirm('Delete this letter forever? 🐾');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="back_profile" value="<?= $filterProfile ?>">
                    <input type="hidden" name="back_type" value="<?= e($filterType) ?>">
                    <input type="hidden" name="back_status" value="<?= e($filterStatus) ?>">
                    <button type="submit" class="act act-danger" title="Delete"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
