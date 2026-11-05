<?php
/** Cat-style 500 error page. */
$title = $title ?? 'Something broke';
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
        <div class="error-face" aria-hidden="true">😿</div>
        <h1 class="error-title">Oops… something exploded.</h1>
        <p class="error-text">The cat is on it. Please try again in a moment. 🧹</p>
        <div class="error-actions">
            <a class="btn-primary" href="<?= e(url('/')) ?>">Back to the start</a>
        </div>
    </main>
</body>
</html>
