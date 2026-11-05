<?php
/**
 * Admin — profiles list. Data: $profiles, $flash
 */

$statuses = ['draft' => ['Draft', 'pill-draft'], 'scheduled' => ['Scheduled', 'pill-scheduled'],
             'published' => ['Published', 'pill-published'], 'paused' => ['Paused', 'pill-paused'],
             'archived' => ['Archived', 'pill-archived']];
?>
<div class="page-head">
    <div>
        <h1 class="welcome-title">Birthday profiles 🎂</h1>
        <p class="welcome-sub">Every person gets one link, one password, one story.</p>
    </div>
    <a class="btn-primary" href="<?= e(url('/admin/profiles/new')) ?>">
        <i class="fa-solid fa-plus" aria-hidden="true"></i> New profile
    </a>
</div>

<?php if (count($profiles) === 0): ?>
    <div class="glass-card empty-card">
        <p class="empty-face" aria-hidden="true">🙀</p>
        <h2>No profiles yet</h2>
        <p class="welcome-sub">Create the first birthday profile and the link will be ready to share.</p>
        <a class="btn-primary" href="<?= e(url('/admin/profiles/new')) ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i> Create profile</a>
    </div>

<?php else: ?>
    <div class="profile-grid">
        <?php foreach ($profiles as $p): [$pillText, $pillClass] = $statuses[$p['status']] ?? [$p['status'], 'pill-draft']; ?>
        <article class="profile-card glass-card">
            <header class="pc-head">
                <span class="pc-initial" aria-hidden="true"><?= e(strtoupper(substr((string) $p['display_name'], 0, 1))) ?></span>
                <div class="pc-title">
                    <h2><?= e((string) $p['display_name']) ?></h2>
                    <span class="status-pill <?= $pillClass ?>"><?= e($pillText) ?></span>
                </div>
            </header>

            <dl class="pc-meta">
                <div><dt>Birthday</dt><dd><?= e((string) $p['birthday_at']) ?> · <?= e((string) $p['timezone']) ?></dd></div>
                <div><dt>Link</dt><dd><code class="pc-link">/b/<?= e((string) $p['slug']) ?></code></dd></div>
            </dl>

            <div class="pc-actions">
                <a class="act act-primary" href="<?= e(url('/b/' . $p['slug'])) ?>" target="_blank" title="Preview as visitor">
                    <i class="fa-solid fa-eye" aria-hidden="true"></i> Preview</a>
                <a class="act" href="<?= e(url('/admin/profiles/' . $p['id'] . '/edit')) ?>" title="Edit profile">
                    <i class="fa-solid fa-pen" aria-hidden="true"></i> Edit</a>
                <a class="act" href="<?= e(url('/admin/profiles/' . $p['id'] . '/content')) ?>" title="Content">
                    <i class="fa-solid fa-feather" aria-hidden="true"></i> Content</a>
                <a class="act" href="<?= e(url('/admin/profiles/' . $p['id'] . '/media')) ?>" title="Media">
                    <i class="fa-solid fa-images" aria-hidden="true"></i> Media</a>

                <?php if ($p['status'] === 'published'): ?>
                    <form method="post" action="<?= e(url('/admin/profiles/' . $p['id'] . '/unpublish')) ?>">
                        <?= csrf_field() ?><button type="submit" class="act"><i class="fa-solid fa-toggle-on" aria-hidden="true"></i> Unpublish</button></form>
                <?php elseif (in_array($p['status'], ['draft', 'scheduled', 'paused', 'archived'], true) && $p['status'] !== 'archived'): ?>
                    <form method="post" action="<?= e(url('/admin/profiles/' . $p['id'] . '/publish')) ?>">
                        <?= csrf_field() ?><button type="submit" class="act"><i class="fa-solid fa-toggle-off" aria-hidden="true"></i> Publish</button></form>
                <?php endif; ?>
                <?php if ($p['status'] !== 'archived'): ?>
                    <form method="post" action="<?= e(url('/admin/profiles/' . $p['id'] . '/archive')) ?>">
                        <?= csrf_field() ?><button type="submit" class="act"><i class="fa-solid fa-box-archive" aria-hidden="true"></i> Archive</button></form>
                <?php else: ?>
                    <form method="post" action="<?= e(url('/admin/profiles/' . $p['id'] . '/restore')) ?>">
                        <?= csrf_field() ?><button type="submit" class="act"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Restore</button></form>
                <?php endif; ?>
                <form method="post" action="<?= e(url('/admin/profiles/' . $p['id'] . '/delete')) ?>"
                      onsubmit="return confirm('Delete this profile and all its content permanently?');">
                    <?= csrf_field() ?><button type="submit" class="act act-danger"><i class="fa-solid fa-trash" aria-hidden="true"></i> Delete</button></form>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
