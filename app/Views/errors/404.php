<?php
/** Cat-style 404 page. */
$title = $title ?? 'Page not found';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · Birthday Surprise</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🐾</text></svg>">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Nunito:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="error-page">
    <main class="error-stage">
        <div class="error-face" aria-hidden="true">🙀</div>
        <h1 class="error-title">Meow? This page ran away…</h1>
        <p class="error-text">The link may be wrong — or the cat ate it. 🐾</p>
        <div class="error-actions">
            <a class="btn-primary" href="<?= e(url('/')) ?>">Back to the start</a>
            <a class="btn-secondary" href="<?= e(url('/admin/login')) ?>">Admin login</a>
        </div>
    </main>
</body>
</html>
