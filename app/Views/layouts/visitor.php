<?php
/**
 * Visitor layout for birthday pages — Pastel Purr.
 * Data: $title, $content. Optional: $meta description.
 */

$meta = $meta ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title ?? 'Birthday Surprise') ?></title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🐾</text></svg>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&family=Nunito:wght@400;600;700&family=Pacifico&family=Caveat:wght@500;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/visitor.css')) ?>?v=13">
</head>
<body class="visit-page site-bg"
      data-flash-type="<?= e(($visitFlash['type'] ?? '') !== '' ? $visitFlash['type'] : '') ?>"
      data-flash-message="<?= e($visitFlash['message'] ?? '') ?>"
      data-csrf="<?= e(csrf_token()) ?>">

    <div class="ambient" aria-hidden="true">
        <span class="floater" style="left:5%;  font-size:22px; --delay:0s;   --dur:16s;">🎈</span>
        <span class="floater floater-star" style="left:13%; font-size:14px; --delay:3s; --dur:13s;">✦</span>
        <span class="floater" style="left:22%;  font-size:18px; --delay:6s;  --dur:17s;">💗</span>
        <span class="floater floater-star" style="left:33%; font-size:12px; --delay:1.5s; --dur:14s;">✦</span>
        <span class="floater" style="left:45%;  font-size:20px; --delay:8s;  --dur:18s;">⭐</span>
        <span class="floater" style="left:56%;  font-size:16px; --delay:4s;  --dur:15s;">💜</span>
        <span class="floater" style="left:66%;  font-size:22px; --delay:10s; --dur:16s;">🎈</span>
        <span class="floater floater-star" style="left:75%; font-size:13px; --delay:2.4s; --dur:12s;">✦</span>
        <span class="floater" style="left:84%;  font-size:18px; --delay:7s;  --dur:15s;">💖</span>
        <span class="floater" style="left:93%;  font-size:20px; --delay:11s; --dur:18s;">🎁</span>
    </div>

    <main class="visit-stage"><?= $content /* trusted internal template */ ?></main>

    <footer class="visit-foot">🎂 made with <span aria-hidden="true">💖</span> · Birthday Surprise</footer>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
    <script src="<?= e(asset('js/visitor.js')) ?>?v=13" defer></script>
</body>
</html>
