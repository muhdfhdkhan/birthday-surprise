<?php
/**
 * Admin — media manager for one profile.
 * Data: $profile, $images, $assets, $cats, $flash
 */

$imgUrl = static fn(string $rel): string => url('uploads/' . ltrim(str_replace('\\', '/', $rel), '/'));
?>
<div class="page-head">
    <div>
        <h1 class="welcome-title">Media · <?= e((string) $profile['display_name']) ?> 🖼️</h1>
        <p class="welcome-sub">Photos for the memories stage, background music and sound effects.</p>
    </div>
    <div class="head-actions">
        <a class="btn-secondary" href="<?= e(url('/admin/profiles')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Profiles</a>
        <a class="btn-secondary" href="<?= e(url('/admin/profiles/' . $profile['id'] . '/content')) ?>"><i class="fa-solid fa-feather" aria-hidden="true"></i> Content</a>
        <a class="btn-secondary" target="_blank" href="<?= e(url('/b/' . $profile['slug'])) ?>"><i class="fa-solid fa-eye" aria-hidden="true"></i> Preview</a>
    </div>
</div>

<?php /* ---------- gallery ---------- */ ?>
<section class="glass-card form-card">
    <h2 class="card-title"><i class="fa-solid fa-images" aria-hidden="true"></i> Photo gallery (<?= count($images) ?>) — shown in the Memories stage</h2>

    <form method="post" action="<?= e(url('/admin/profiles/' . $profile['id'] . '/gallery')) ?>" enctype="multipart/form-data" class="upload-row">
        <?= csrf_field() ?>
        <input type="file" name="photos[]" accept=".jpg,.jpeg,.png,.webp" multiple required>
        <button type="submit" class="btn-primary"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i> Upload</button>
        <small class="help">JPG / PNG / WebP · up to 5 MB each · multi-select ok</small>
    </form>

    <?php if (count($images) === 0): ?>
        <p class="empty-inline">No photos yet — the memories stage stays hidden until at least one is active.</p>
    <?php else: ?>
        <div class="media-grid">
        <?php foreach ($images as $idx => $img): ?>
            <div class="media-tile <?= (int) $img['is_active'] === 1 ? '' : 'is-off' ?>">
                <img src="<?= e($imgUrl((string) ($img['thumb_name'] ?: $img['file_name']))) ?>" alt="">
                <?php if ((int) $img['is_cover'] === 1): ?><span class="cover-flag">cover</span><?php endif; ?>

                <form method="post" action="<?= e(url('/admin/profiles/' . $profile['id'] . '/gallery/' . $img['id'] . '/update')) ?>" class="tile-form">
                    <?= csrf_field() ?>
                    <input type="text" name="caption" maxlength="255" value="<?= e((string) $img['caption']) ?>" placeholder="Caption">
                    <input type="text" name="alt_text" maxlength="255" value="<?= e((string) $img['alt_text']) ?>" placeholder="Alt text">
                    <input type="text" name="memory_note" maxlength="1000" value="<?= e((string) $img['memory_note']) ?>" placeholder="Memory note">
                    <div class="mini-inputs">
                        <input type="date" name="taken_on" value="<?= e((string) ($img['taken_on'] ?? '')) ?>" title="Taken on">
                        <label class="switch-row inline"><input type="checkbox" name="is_active" <?= (int) $img['is_active'] === 1 ? 'checked' : '' ?>> active</label>
                        <button type="submit" class="act act-primary"><i class="fa-solid fa-check" aria-hidden="true"></i></button>
                    </div>
                </form>

                <div class="tile-actions">
                    <form method="post" action="<?= e(url('/admin/profiles/' . $profile['id'] . '/gallery/' . $img['id'] . '/move')) ?>">
                        <?= csrf_field() ?><input type="hidden" name="direction" value="<?= $idx === 0 ? 'down' : 'up' ?>">
                        <button type="submit" class="act" title="<?= $idx === 0 ? 'Move down' : 'Move up' ?>"><i class="fa-solid fa-<?= $idx === 0 ? 'arrow-down' : 'arrow-up' ?>" aria-hidden="true"></i></button>
                    </form>
                    <?php if ((int) $img['is_cover'] !== 1): ?>
                    <form method="post" action="<?= e(url('/admin/profiles/' . $profile['id'] . '/gallery/' . $img['id'] . '/cover')) ?>">
                        <?= csrf_field() ?><button type="submit" class="act" title="Make gallery cover"><i class="fa-solid fa-star" aria-hidden="true"></i></button>
                    </form>
                    <?php endif; ?>
                    <form method="post" action="<?= e(url('/admin/profiles/' . $profile['id'] . '/gallery/' . $img['id'] . '/delete')) ?>" onsubmit="return confirm('Delete this photo?');">
                        <?= csrf_field() ?><button type="submit" class="act act-danger"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php /* ---------- audio ---------- */ ?>
<section class="glass-card form-card">
    <h2 class="card-title"><i class="fa-solid fa-music" aria-hidden="true"></i> Background music &amp; sound effects</h2>

    <div class="grid-2">
        <form method="post" action="<?= e(url('/admin/profiles/' . $profile['id'] . '/audio')) ?>" enctype="multipart/form-data" class="upload-row">
            <?= csrf_field() ?>
            <input type="hidden" name="kind" value="music">
            <label class="lbl-tiny">Music (one track replaces the old one · MP3/OGG up to 12 MB)</label>
            <input type="file" name="audio" accept=".mp3,.ogg" required>
            <input type="text" name="label" maxlength="120" placeholder="Label e.g. Our song 🎶">
            <button type="submit" class="btn-primary"><i class="fa-solid fa-music" aria-hidden="true"></i> Upload music</button>
        </form>

        <form method="post" action="<?= e(url('/admin/profiles/' . $profile['id'] . '/audio')) ?>" enctype="multipart/form-data" class="upload-row">
            <?= csrf_field() ?>
            <input type="hidden" name="kind" value="sfx">
            <label class="lbl-tiny">Sound effect (MP3/OGG/WAV up to 4 MB)</label>
            <input type="file" name="audio" accept=".mp3,.ogg,.wav" required>
            <input type="text" name="label" maxlength="120" placeholder="Label e.g. Sparkle ✨">
            <button type="submit" class="btn-secondary"><i class="fa-solid fa-bell" aria-hidden="true"></i> Upload effect</button>
        </form>
    </div>

    <?php $rows = array_values(array_filter($assets, static fn($a) => ($a['kind'] ?? '') !== 'animation')); ?>
    <?php if (count($rows) === 0): ?>
        <p class="empty-inline">No audio yet.</p>
    <?php else: ?>
        <table class="grant-table">
            <thead><tr><th>Kind</th><th>Label</th><th>File</th><th>Uploaded</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $a): ?>
            <tr>
                <td><span class="status-pill <?= ($a['kind'] ?? '') === 'music' ? 'pill-published' : 'pill-scheduled' ?>"><?= e((string) $a['kind']) ?></span></td>
                <td><?= e((string) $a['label']) ?></td>
                <td><code class="code-inline"><?= e((string) $a['file_name']) ?></code></td>
                <td><?= e((string) $a['created_at']) ?></td>
                <td>
                    <audio controls preload="none" src="<?= e($imgUrl((string) $a['file_name'])) ?>" class="mini-audio"></audio>
                    <form method="post" action="<?= e(url('/admin/profiles/' . $profile['id'] . '/audio/' . $a['id'] . '/delete')) ?>" onsubmit="return confirm('Delete this audio?');">
                        <?= csrf_field() ?><button type="submit" class="act act-danger"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<?php /* ---------- cat characters ---------- */ ?>
<section class="glass-card form-card">
    <h2 class="card-title"><i class="fa-solid fa-paw" aria-hidden="true"></i> Cat characters (built-in)</h2>
    <p class="soon-note">These emoji cats animate every stage — apologies, wishes and the finale. They work offline and need no uploads.</p>
    <div class="cat-grid">
        <?php foreach ($cats as $key => [$emoji, $name, $action]): ?>
        <div class="cat-card">
            <span class="cat-emoji" aria-hidden="true"><?= $emoji ?></span>
            <b><?= e($name) ?></b>
            <span class="muted"><?= e($action) ?></span>
            <code class="code-inline"><?= e($key) ?></code>
        </div>
        <?php endforeach; ?>
    </div>
</section>
