<?php
/**
 * Admin — access requests + re-entry codes. Data: $requests, $grants, $flash, $grantInfo
 */

$statusPill = [
    'pending'  => ['Pending', 'pill-scheduled'],
    'approved' => ['Approved', 'pill-published'],
    'rejected' => ['Rejected', 'pill-archived'],
    'expired'  => ['Expired', 'pill-draft'],
];
?>
<div class="page-head">
    <div>
        <h1 class="welcome-title">Access requests 🔑</h1>
        <p class="welcome-sub">Approve a locked-out visitor, or hand them a one-time code.</p>
    </div>
    <a class="btn-secondary" href="<?= e(url('/admin/profiles')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Profiles</a>
</div>

<?php if (!empty($grantInfo)): ?>
<section class="glass-card grant-peek" id="grantPeek" role="alert">
    <h2 class="card-title"><i class="fa-solid fa-ticket" aria-hidden="true"></i> New re-entry code — shown only once!</h2>
    <p class="grant-code" id="grantCode"><?= e($grantInfo['code']) ?></p>
    <p class="soon-note">Valid <?= (int) $grantInfo['expires_in_hours'] ?> h · copy it now, only the hash is stored.</p>
    <div class="head-actions" style="margin-top:10px">
        <button type="button" class="btn-primary" id="copyCodeBtn"><i class="fa-solid fa-copy" aria-hidden="true"></i> Copy code</button>
    </div>
</section>
<?php endif; ?>

<section class="glass-card form-card">
    <h2 class="card-title"><i class="fa-solid fa-inbox" aria-hidden="true"></i> Requests (<?= count($requests) ?>)</h2>

    <?php if (count($requests) === 0): ?>
        <p class="empty-inline">No access requests yet — the gate is keeping everyone out politely 🐱</p>
    <?php else: ?>
        <?php foreach ($requests as $r): [$pillText, $pillClass] = $statusPill[$r['status']] ?? [$r['status'], 'pill-draft']; ?>
        <div class="list-row req-row">
            <div class="req-info">
                <div class="req-head">
                    <b><?= e((string) ($r['profile_name'] ?? 'Deleted profile')) ?></b>
                    <span class="status-pill <?= $pillClass ?>"><?= e($pillText) ?></span>
                    <span class="muted"><?= e((string) $r['created_at']) ?></span>
                </div>
                <?php if (!empty($r['note'])): ?><p class="req-note">“<?= e((string) $r['note']) ?>”</p><?php endif; ?>
                <p class="muted tiny-ip">IP <?= e((string) ($r['ip_address'] ?? '—')) ?><?= $r['status'] !== 'pending' && !empty($r['decided_at']) ? ' · decided ' . e((string) $r['decided_at']) : '' ?><?= !empty($r['reason']) ? ' · ' . e((string) $r['reason']) : '' ?></p>
            </div>

            <?php if ($r['status'] === 'pending'): ?>
            <div class="req-forms">
                <form method="post" action="<?= e(url('/admin/requests/' . $r['id'] . '/approve')) ?>" class="tiny-form">
                    <?= csrf_field() ?>
                    <label class="lbl-tiny">hours <input type="number" name="hours" value="<?= (int) ($defaultWindowHours ?? 24) ?>" min="1" max="168"></label>
                    <label class="lbl-tiny">uses <input type="number" name="uses" value="1" min="1" max="20"></label>
                    <button type="submit" class="act act-primary" title="Approve + issue code"><i class="fa-solid fa-check" aria-hidden="true"></i> Approve</button>
                </form>
                <form method="post" action="<?= e(url('/admin/requests/' . $r['id'] . '/reject')) ?>" class="tiny-form">
                    <?= csrf_field() ?>
                    <input type="text" name="reason" maxlength="255" placeholder="Reason (optional)">
                    <button type="submit" class="act act-danger"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Reject</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<section class="glass-card form-card">
    <h2 class="card-title"><i class="fa-solid fa-ticket" aria-hidden="true"></i> Recent re-entry codes (<?= count($grants) ?>)</h2>

    <?php if (count($grants) === 0): ?>
        <p class="empty-inline">No codes issued yet.</p>
    <?php else: ?>
        <table class="grant-table">
            <thead><tr><th>Profile</th><th>Created</th><th>Expires</th><th>Uses left</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($grants as $g):
                $open  = (int) $g['is_revoked'] === 0 && (int) $g['uses_left'] > 0;
                $state = (int) $g['is_revoked'] === 1 ? ['Revoked', 'pill-archived'] : ((int) $g['uses_left'] <= 0 ? ['Used up', 'pill-draft'] : ['Valid', 'pill-published']);
            ?>
            <tr>
                <td><?= e((string) ($g['profile_name'] ?? '—')) ?></td>
                <td><?= e((string) $g['created_at']) ?></td>
                <td><?= e((string) $g['expires_at']) ?></td>
                <td><?= (int) $g['uses_left'] ?>/<?= (int) $g['max_uses'] ?></td>
                <td><span class="status-pill <?= $state[1] ?>"><?= $state[0] ?></span></td>
                <td>
                    <?php if ($open): ?>
                    <form method="post" action="<?= e(url('/admin/grants/' . $g['id'] . '/revoke')) ?>" onsubmit="return confirm('Revoke this code?');">
                        <?= csrf_field() ?><button type="submit" class="act act-danger"><i class="fa-solid fa-ban" aria-hidden="true"></i> Revoke</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
