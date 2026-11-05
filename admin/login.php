<?php
/**
 * Admin login page — Pastel Purr style.
 * Data: $title, $flash = ['type','message']|null
 */

$flash = $flash ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title ?? 'Admin Login') ?></title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🐾</text></svg>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="login-page"
      data-flash-type="<?= e($flash['type'] ?? '') ?>"
      data-flash-message="<?= e($flash['message'] ?? '') ?>">

    <div class="ambient" aria-hidden="true">
        <span class="floater" style="left:6%;  font-size:24px; --delay:0s;   --dur:13s;">💗</span>
        <span class="floater floater-star" style="left:14%; font-size:15px; --delay:2.5s; --dur:16s;">✦</span>
        <span class="floater" style="left:23%;  font-size:20px; --delay:5s;   --dur:14s;">🎈</span>
        <span class="floater floater-star" style="left:31%; font-size:13px; --delay:1.2s; --dur:18s;">✦</span>
        <span class="floater" style="left:40%;  font-size:22px; --delay:7s;   --dur:15s;">⭐</span>
        <span class="floater" style="left:52%;  font-size:18px; --delay:3.4s; --dur:17s;">💜</span>
        <span class="floater floater-star" style="left:60%; font-size:14px; --delay:8s;   --dur:13s;">✦</span>
        <span class="floater" style="left:68%;  font-size:24px; --delay:4.8s; --dur:16s;">🎈</span>
        <span class="floater" style="left:76%;  font-size:20px; --delay:6.5s; --dur:14s;">💖</span>
        <span class="floater floater-star" style="left:85%; font-size:15px; --delay:2s;   --dur:15s;">✦</span>
        <span class="floater" style="left:92%;  font-size:22px; --delay:9s;   --dur:18s;">🎁</span>
        <span class="floater" style="left:47%;  font-size:16px; --delay:10s;  --dur:19s;">🐾</span>
    </div>

    <main class="login-stage">
        <section class="glass-card login-card" id="loginCard">
            <div class="brand-badge" id="brandPaw"><i class="fa-solid fa-paw"></i></div>
            <h1 class="login-title">Admin Login</h1>
            <p class="login-sub">Meow! Only the birthday wizard may pass 🐾</p>

            <?php if (!empty($flash)): ?>
                <div class="form-alert <?= $flash['type'] === 'success' ? 'is-success' : '' ?>" role="alert">
                    <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('/admin/login')) ?>" id="loginForm">
                <?= csrf_field() ?>

                <div class="field">
                    <label for="username">Username or email</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-user input-icon" aria-hidden="true"></i>
                        <input type="text" id="username" name="username"
                               required autocomplete="username" placeholder="your username">
                    </div>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
                        <input type="password" id="password" name="password"
                               required autocomplete="current-password" placeholder="••••••••">
                        <button type="button" class="pw-toggle" id="pwToggle" aria-label="Show password">
                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-block" id="loginBtn">
                    <span class="btn-label">Sign in</span>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </button>
            </form>

            <p class="privacy-line"><i class="fa-solid fa-shield-heart"></i>
                We log sign-in attempts and IP addresses to keep this page safe.
            </p>
        </section>

        <p class="login-footer">🎂 Birthday Surprise · Pastel Purr</p>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="<?= e(asset('js/login.js')) ?>" defer></script>
</body>
</html>
