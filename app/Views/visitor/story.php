<?php
/**
 * The birthday story — progressed stage by stage with vanilla JS.
 * Data: $profile, $slug, $content, $sections, $sorry, $wishes, $images,
 *       $music, $unlockedUntil
 */

$slug      = (string) $slug;
$sections  = $sections;
$content   = $content;
$has       = static fn(string $key): bool => isset($sections[$key]);
$imageUrl  = static fn(string $rel): string => url('uploads/' . ltrim(str_replace('\\', '/', $rel), '/'));
$cats      = [
    'crying' => '😿', 'begging' => '🥺', 'hiding' => '🙈', 'rolling' => '😹',
    'angry' => '😾', 'dancing' => '💃', 'love' => '😻', 'welcome' => '🐱',
    'sleepy' => '😴', 'peek' => '👀',
];
$chips = array_filter(array_map('trim', explode(',', (string) ($content['gift_categories'] ?? ''))));
$secretRule = (string) ($content['secret_unlock_rule'] ?? 'after_sorry');
$showSecretRule = $secretRule === 'manual' ? 'manual' : ($secretRule === 'after_candles' ? 'after_candles' : ($secretRule === 'after_sorry' ? 'after_sorry' : ''));
$stageIndex = 0;
?>
<?php /* early-peek countdown banner */ ?>
<?php if ((string) $profile['block_before_birthday'] === '1' && !empty($targetIso)): ?>
<div class="peek-banner" id="peekCounter" data-target="<?= e($targetIso) ?>">Pre-birthday peek: <b id="peekTimer"></b></div>
<?php endif; ?>

<!-- tiny flag block so JS knows the layout -->
<div id="storyFlags" hidden
      data-slug="<?= e($slug) ?>"
      data-name="<?= e((string) $profile['display_name']) ?>"
      data-secret-rule="<?= e($showSecretRule) ?>"></div>


<?php /* ---------- 1 · WELCOME ---------- */ ?>
<?php if ($has('welcome')): ?>
<section class="story-card glass-card stage" data-stage="welcome">
    <div class="cat-face cat-idle" aria-hidden="true">🐱</div>
    <h1 class="display-title">Something special…</h1>
    <p class="lead"><?= nl2br(e((string) ($content['welcome_message'] ?? ''))) ?></p>
    <button type="button" class="btn-primary btn-next" data-next>
        <span class="btn-label">Open your surprise 🎁</span><i class="fa-solid fa-arrow-down" aria-hidden="true"></i>
    </button>
</section>
<?php endif; ?>

<?php /* ---------- 2 · SORRY SEQUENCE ---------- */ ?>
<?php if ($has('sorry') && !empty($sorry)): $i = 0; foreach ($sorry as $apology): $i++; ?>
<section class="story-card glass-card stage" data-stage="sorry" data-index="<?= $i ?>" data-total="<?= count($sorry) ?>">
    <?php /* proper CSS cat — starts mildly sad and gets more theatrical
               with every "still angry" click (see visitor.css .sorry-cat) */ ?>
    <div class="sorry-cat" data-sad="0" aria-hidden="true">
        <div class="sc-inner">
            <span class="sc-ear e-l"></span><span class="sc-ear e-r"></span>
            <div class="sc-head">
                <span class="sc-ear-inner e-l"></span><span class="sc-ear-inner e-r"></span>
                <span class="sc-blush b-l"></span><span class="sc-blush b-r"></span>
                <span class="sc-eye e-l"><b class="sc-tear"></b><i class="sc-lid"></i></span>
                <span class="sc-eye e-r"><b class="sc-tear"></b><i class="sc-lid"></i></span>
                <span class="sc-muzzle">
                    <i class="sc-nose"></i><span class="sc-mouth"></span>
                    <s class="sc-whisk w-l1"></s><s class="sc-whisk w-l2"></s>
                    <s class="sc-whisk w-r1"></s><s class="sc-whisk w-r2"></s>
                </span>
            </div>
            <div class="sc-body">
                <span class="sc-paw p-l"></span><span class="sc-paw p-r"></span>
                <span class="sc-sign">SORRY!</span>
            </div>
            <span class="sc-tail"></span>
            <span class="sc-puddle"></span>
            <span class="sc-heart h1">💖</span><span class="sc-heart h2">💗</span><span class="sc-heart h3">💝</span>
        </div>
    </div>
    <p class="counter">Apology <b><?= $i ?></b> of <?= count($sorry) ?></p>
    <div class="plate-card"><p class="plate-text"><?= nl2br(e((string) $apology['body'])) ?></p></div>
    <div class="dual-actions">
        <button type="button" class="btn-primary btn-forgive" data-forgive>
            <span class="btn-label"><?= e((string) ($apology['forgive_label'] ?? 'Okay, I forgive you 💖')) ?></span></button>
        <button type="button" class="btn-secondary btn-angry" data-angry>
            <span class="btn-label"><?= e((string) ($apology['angry_label'] ?? 'Still angry 😾')) ?></span></button>
    </div>
    <p class="angry-note" data-angry-note hidden></p>
</section>
<?php endforeach; endif; ?>

<?php /* ---------- 3 · BIRTHDAY REVEAL ---------- */ ?>
<?php if ($has('reveal')): ?>
<section class="story-card glass-card stage stage-reveal" data-stage="reveal">
    <h1 class="gold-title" id="bigHbTitle">HAPPY BIRTHDAY</h1>
    <p class="script-name"><?= e((string) $profile['display_name']) ?></p>
    <div class="cake" id="cake" aria-label="Tap to blow the candles">
        <div class="cake-img" aria-hidden="true">🎂</div>
        <div class="candle-hint" id="candleHint">Tap to blow 🕯️</div>
    </div>
    <div class="dual-actions">
        <button type="button" class="btn-primary btn-next" data-next>
            <span class="btn-label">Keep going 💫</span></button>
    </div>
</section>
<?php endif; ?>

<?php /* ---------- 4 · MEMORIES ---------- */ ?>
<?php if ($has('memories') && count($images) > 0): ?>
<section class="story-card glass-card stage" data-stage="memories">
    <div class="cat-face cat-idle" aria-hidden="true">📸</div>
    <h2 class="display-title">Our memories</h2>
    <p class="lead">A few moments frozen in time ✨</p>
    <div class="polaroid-grid" data-gallery>
        <?php foreach ($images as $img): ?>
        <figure class="polaroid" data-photo data-full="<?= e($imageUrl((string) $img['file_name'])) ?>"
                data-caption="<?= e((string) ($img['caption'] ?? ($profile['display_name'] ?? ''))) ?>"
                tabindex="0" role="button" aria-label="Open photo">
            <img src="<?= e($imageUrl((string) ($img['thumb_name'] ?: $img['file_name']))) ?>"
                 alt="<?= e((string) ($img['alt_text'] ?? 'Memory photo')) ?>" loading="lazy">
            <?php if (!empty($img['taken_on'])): ?><figcaption class="taken-on"><?= e((string) $img['taken_on']) ?></figcaption><?php endif; ?>
        </figure>
        <?php endforeach; ?>
    </div>
    <button type="button" class="btn-primary btn-next" data-next><span class="btn-label">Next, with a smile 😊</span></button>
</section>
<?php endif; ?>
<?php if ($has('letter') && !empty($content['letter_text'])): ?>
<?php /* ---------- 5 · LETTER + WISHES + SECRET ---------- */ ?>
<section class="story-card glass-card stage" data-stage="letter">
    <div class="cat-face cat-idle" aria-hidden="true">✉️</div>
    <h2 class="display-title">A letter for you</h2>
    <div class="letter-paper" id="letterPaper">
        <div class="letter-text" data-typewriter><?= nl2br(e((string) $content['letter_text'])) ?></div>
    </div>
    <button type="button" class="btn-ghost" data-skip-typewriter>Skip typing →</button>

    <?php if (!empty($wishes)): ?>
    <div class="wish-stack">
        <?php foreach ($wishes as $wish): ?>
        <div class="wish-card"><span aria-hidden="true"><?= $cats[$wish['cat_action']] ?? '😻' ?></span>
            <p><?= nl2br(e((string) $wish['body'])) ?></p></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($showSecretRule !== ''): ?>
    <div class="secret-box" data-secret hidden>
        <div class="wax-seal" aria-hidden="true">💧</div>
        <p class="lead"><?= nl2br(e((string) ($content['secret_message'] ?? 'psst…'))) ?></p>
    </div>
    <?php endif; ?>

    <button type="button" class="btn-primary btn-next" data-next><span class="btn-label">Almost done… 💫</span></button>
</section>
<?php endif; ?>

<?php /* ---------- 6 · REPLIES (gift + complaint) ---------- */ ?>
<?php if ($has('replies')): ?>
<section class="story-card glass-card stage" data-stage="replies">
    <h2 class="display-title">Before the curtain falls…</h2>

    <form class="reply-box" data-reply data-type="gift">
        <?= csrf_field() ?>
        <h3 class="reply-title"><i class="fa-solid fa-gift" aria-hidden="true"></i> Which gift do you want?</h3>

        <?php if (count($chips) > 0): ?>
        <div class="chip-row">
            <?php foreach ($chips as $i => $chip): ?>
            <button type="button" class="chip" data-chip="<?= e($chip) ?>"><?= e($chip) ?></button>
            <?php endforeach; ?>
        </div>
        <input type="hidden" name="category" data-category value="">
        <?php endif; ?>

        <div class="field">
            <textarea name="body" rows="3" maxlength="1000" placeholder="I want… 🎁"></textarea>
        </div>
        <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
        <button type="submit" class="btn-primary btn-block"><span class="btn-label">Send with paws 🐾</span></button>
        <p class="reply-note" data-note hidden></p>
    </form>

    <form class="reply-box" data-reply data-type="complaint">
        <?= csrf_field() ?>
        <h3 class="reply-title"><i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i>
            Any complaint — or something you want to tell me?</h3>
        <div class="field">
            <textarea name="body" rows="4" maxlength="1000"
                placeholder="<?= e((string) ($content['complaint_placeholder'] ?? "I'm all ears… 🐱")) ?>"></textarea>
        </div>
        <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
        <button type="submit" class="btn-primary btn-block"><span class="btn-label">Send gently 💌</span></button>
        <p class="reply-note" data-note hidden></p>
    </form>

    <p class="reply-note finale-lock" data-finale-note hidden></p>
    <button type="button" class="btn-secondary btn-next" data-next><span class="btn-label">To the finale 🎈</span></button>
</section>
<?php endif; ?>

<?php /* ---------- 7 · FINALE ---------- */ ?>
<?php if ($has('finale')): ?>
<section class="story-card glass-card stage" data-stage="finale">
    <div class="cat-face cat-love" aria-hidden="true">😻</div>
    <h2 class="display-title">And that's not all…</h2>
    <p class="final-script"><?= nl2br(e((string) ($content['final_message'] ?? 'You deserved the whole world today 🌍💖'))) ?></p>

    <?php if (!empty($content['post_birthday_message'])): ?>
    <p class="post-note"><?= nl2br(e((string) $content['post_birthday_message'])) ?></p>
    <?php endif; ?>

    <div class="reaction-row" aria-label="How did it feel?">
        <?php foreach (['❤️', '😂', '😭', '🎂'] as $rx): ?>
        <button type="button" class="reaction" data-reaction="<?= $rx ?>"><?= $rx ?></button>
        <?php endforeach; ?>
    </div>

    <div class="dual-actions">
        <button type="button" class="btn-secondary" data-replay onclick="location.reload()">
            <span class="btn-label"><i class="fa-solid fa-rotate-right" aria-hidden="true"></i> Replay</span></button>
        <?php if ((int) ($profile['allow_share'] ?? 0) === 1): ?>
        <button type="button" class="btn-secondary" id="shareBtn"><i class="fa-solid fa-share-nodes" aria-hidden="true"></i> Share</button>
        <?php endif; ?>
    </div>
    <p class="small-note">Access to this page stays open until <span data-until="<?= (int) $unlockedUntil ?>"></span>.</p>
</section>
<?php endif; ?>

<?php /* lightbox */ ?>
<div class="lightbox" id="lightbox" hidden>
    <button type="button" class="lb-close" aria-label="Close escape">&times;</button>
    <img src="" alt="">
    <div class="lb-caption"></div>
</div>

<?php if (!empty($music)): ?>
<div class="music-pill" id="musicPill" title="<?= e((string) $music['label']) ?>">
    <button type="button" id="musicBtn" aria-label="Play music"><i class="fa-solid fa-play" aria-hidden="true"></i></button>
    <audio id="musicPlayer" src="<?= e(url('uploads/' . $music['file_name'])) ?>" loop preload="none"></audio>
</div>
<?php endif; ?>
