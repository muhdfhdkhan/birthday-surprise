<?php
/**
 * Admin — content editor. Data: $profile, $content, $sections, $sorry, $wishes, $flash
 */

$c = $content ?? [];
$has = static fn(string $s): bool => isset($sections[$s]);
$catOptions = static fn(array $list, string $current): string => implode('', array_map(
    static fn($cat) => '<option value="' . e($cat) . '"' . ((string) $current === $cat ? ' selected' : '') . '>' . e($cat) . '</option>',
    $list
));
?>
<div class="page-head">
    <div>
        <h1 class="welcome-title">Content · <?= e((string) $profile['display_name']) ?> 🎨</h1>
        <p class="welcome-sub">Everything the visitor will see, stage by stage.</p>
    </div>
    <div class="head-actions">
        <a class="btn-secondary" href="<?= e(url('/admin/profiles')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Profiles</a>
        <a class="btn-secondary" href="<?= e(url('/admin/profiles/' . $profile['id'] . '/media')) ?>"><i class="fa-solid fa-images" aria-hidden="true"></i> Media</a>
        <a class="btn-secondary" target="_blank" href="<?= e(url('/b/' . $profile['slug'])) ?>"><i class="fa-solid fa-eye" aria-hidden="true"></i> Preview</a>
    </div>
</div>

<form method="post" action="<?= e(url('/admin/profiles/' . $profile['id'] . '/content')) ?>">
    <?= csrf_field() ?>

    <section class="glass-card form-card">
        <h2 class="card-title"><i class="fa-solid fa-flag" aria-hidden="true"></i> Stage &amp; secret settings</h2>

        <div class="chip-toggle-grid">
            <?php foreach (['welcome', 'sorry', 'reveal', 'memories', 'letter', 'replies', 'finale'] as $sec): ?>
                <label class="chip-toggle <?= $has($sec) ? 'is-on' : '' ?>">
                    <input type="checkbox" name="sections[]" value="<?= $sec ?>" <?= $has($sec) ? 'checked' : '' ?>><?= ucfirst($sec) ?></label>
            <?php endforeach; ?>
        </div>

        <div class="grid-3">
            <div class="field">
                <label for="secret_unlock_rule">Secret message unlocks…</label>
                <select id="secret_unlock_rule" name="secret_unlock_rule">
                    <?php foreach (['after_sorry' => 'after the sorry sequence', 'after_candles' => 'after the candles are blown', 'manual' => 'manually (skip button shown)', 'never' => 'never (hidden)'] as $ruleKey => $ruleText): ?>
                        <option value="<?= $ruleKey ?>" <?= ((string) ($c['secret_unlock_rule'] ?? 'after_sorry')) === $ruleKey ? 'selected' : '' ?>><?= $ruleText ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label class="switch-row inline"><input type="checkbox" name="sorry_required" <?= (int) ($c['sorry_required'] ?? 1) === 1 ? 'checked' : '' ?>><span>Sorry sequence is required (not skippable)</span></label>
            </div>
            <div class="field">
                <label for="complaint_placeholder">Complaint box placeholder</label>
                <input type="text" id="complaint_placeholder" name="complaint_placeholder" maxlength="255"
                       value="<?= e((string) ($c['complaint_placeholder'] ?? '')) ?>" placeholder="Tell me everything — your feelings matter 💌">
            </div>
        </div>
    </section>

    <section class="glass-card form-card">
        <h2 class="card-title"><i class="fa-solid fa-envelope" aria-hidden="true"></i> Messages</h2>

        <div class="field">
            <label for="welcome_message">Welcome message (stage 1)</label>
            <textarea id="welcome_message" name="welcome_message" rows="2" maxlength="1000"
                placeholder="Meow! Something special is waiting for you… 🎁"><?= e((string) ($c['welcome_message'] ?? '')) ?></textarea>
        </div>

        <div class="field">
            <label for="letter_text">Personal letter (typewriter effect) — simple HTML allowed: &lt;p&gt; &lt;b&gt; &lt;i&gt;</label>
            <textarea id="letter_text" name="letter_text" rows="8" maxlength="20000"
                placeholder="My dearest…"><?= e((string) ($c['letter_text'] ?? '')) ?></textarea>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="secret_message">Secret message</label>
                <textarea id="secret_message" name="secret_message" rows="3" maxlength="1000"
                    placeholder="psst… the secret is…"><?= e((string) ($c['secret_message'] ?? '')) ?></textarea>
            </div>
            <div class="field">
                <label for="final_message">Final message (finale)</label>
                <textarea id="final_message" name="final_message" rows="3" maxlength="1000"
                    placeholder="You deserved the whole world today 🌍💖"><?= e((string) ($c['final_message'] ?? '')) ?></textarea>
            </div>
        </div>

        <div class="field">
            <label for="post_birthday_message">Post-birthday message (optional)</label>
            <input type="text" id="post_birthday_message" name="post_birthday_message" maxlength="500"
                   value="<?= e((string) ($c['post_birthday_message'] ?? '')) ?>" placeholder="Belated wishes count too 😉">
        </div>

        <div class="field">
            <label for="gift_categories">Gift chips (comma-separated)</label>
            <input type="text" id="gift_categories" name="gift_categories" maxlength="500"
                   value="<?= e((string) ($c['gift_categories'] ?? '')) ?>" placeholder="Flowers, Cake, Trip, Book">
        </div>
    </section>

    <button type="submit" class="btn-primary btn-block"><span class="btn-label">Save content ✔</span></button>
</form>

<?php /* ---------- sorry messages ---------- */ ?>
<section class="glass-card form-card">
    <h2 class="card-title"><i class="fa-solid fa-face-sad-tear" aria-hidden="true"></i> Sorry messages (<?= count($sorry) ?>)</h2>

    <?php foreach ($sorry as $idx => $row): ?>
    <form class="list-row" method="post" action="<?= e(url('/admin/profiles/' . $profile['id'] . '/sorry/' . $row['id'] . '/update')) ?>">
        <?= csrf_field() ?>
        <textarea name="body" rows="2" maxlength="1000" required><?= e((string) $row['body']) ?></textarea>
        <div class="mini-inputs">
            <select name="cat_action"><?= $catOptions(App\Models\Sorry::CATS, (string) $row['cat_action']) ?></select>
            <input type="text" name="forgive_label" maxlength="80" value="<?= e((string) $row['forgive_label']) ?>" title="Forgive button label">
            <input type="text" name="angry_label" maxlength="80" value="<?= e((string) $row['angry_label']) ?>" title="Angry button label">
        </div>
        <div class="row-actions">
            <button type="submit" class="act act-primary" title="Save"><i class="fa-solid fa-check" aria-hidden="true"></i></button>
            <a class="act" title="Move up" href="#" hidden>up</a>
        </div>
    </form>
    <form class="inline-move" method="post" action="<?= e(url('/admin/profiles/' . $profile['id'] . '/sorry/' . $row['id'] . '/move')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="direction" value="<?= $idx === 0 ? 'down' : 'up' ?>">
        <button type="submit" class="act" title="<?= $idx === 0 ? 'Move down' : 'Move up' ?>">
            <i class="fa-solid fa-<?= $idx === 0 ? 'arrow-down' : 'arrow-up' ?>" aria-hidden="true"></i></button>
    </form>
    <form class="inline-move" method="post" action="<?= e(url('/admin/profiles/' . $profile['id'] . '/sorry/' . $row['id'] . '/delete')) ?>"
          onsubmit="return confirm('Delete this apology?');">
        <?= csrf_field() ?>
        <button type="submit" class="act act-danger" title="Delete"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
    </form>
    <?php /* row separator */ ?>
    <div class="row-sep" hidden></div>
    <?php endforeach; ?>

    <form class="list-row add-row" method="post" action="<?= e(url('/admin/profiles/' . $profile['id'] . '/sorry')) ?>">
        <?= csrf_field() ?>
        <textarea name="body" rows="2" maxlength="1000" required placeholder="Another apology… 🥺"></textarea>
        <div class="mini-inputs">
            <select name="cat_action"><?= $catOptions(App\Models\Sorry::CATS, 'begging') ?></select>
        </div>
        <div class="row-actions">
            <button type="submit" class="btn-secondary"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add</button>
        </div>
    </form>
</section>

<?php /* ---------- wishes ---------- */ ?>
<section class="glass-card form-card">
    <h2 class="card-title"><i class="fa-solid fa-heart" aria-hidden="true"></i> Birthday wishes (<?= count($wishes) ?>)</h2>

    <?php foreach ($wishes as $idx => $row): ?>
    <form class="list-row" method="post" action="<?= e(url('/admin/profiles/' . $profile['id'] . '/wishes/' . $row['id'] . '/update')) ?>">
        <?= csrf_field() ?>
        <textarea name="body" rows="2" maxlength="1000" required><?= e((string) $row['body']) ?></textarea>
        <div class="mini-inputs">
            <select name="cat_action"><?= $catOptions(App\Models\Wish::CATS, (string) $row['cat_action']) ?></select>
        </div>
        <div class="row-actions"><button type="submit" class="act act-primary"><i class="fa-solid fa-check" aria-hidden="true"></i></button></div>
    </form>
    <form class="inline-move" method="post" action="<?= e(url('/admin/profiles/' . $profile['id'] . '/wishes/' . $row['id'] . '/move')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="direction" value="<?= $idx === 0 ? 'down' : 'up' ?>">
        <button type="submit" class="act"><i class="fa-solid fa-<?= $idx === 0 ? 'arrow-down' : 'arrow-up' ?>" aria-hidden="true"></i></button>
    </form>
    <form class="inline-move" method="post" action="<?= e(url('/admin/profiles/' . $profile['id'] . '/wishes/' . $row['id'] . '/delete')) ?>"
          onsubmit="return confirm('Delete this wish?');">
        <?= csrf_field() ?>
        <button type="submit" class="act act-danger"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
    </form>
    <?php endforeach; ?>

    <form class="list-row add-row" method="post" action="<?= e(url('/admin/profiles/' . $profile['id'] . '/wishes')) ?>">
        <?= csrf_field() ?>
        <textarea name="body" rows="2" maxlength="1000" required placeholder="One more wish… 💖"></textarea>
        <div class="mini-inputs">
            <select name="cat_action"><?= $catOptions(App\Models\Wish::CATS, 'dancing') ?></select>
        </div>
        <div class="row-actions">
            <button type="submit" class="btn-secondary"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add</button>
        </div>
    </form>
</section>
