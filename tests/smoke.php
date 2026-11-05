<?php
/**
 * Quick environment smoke test (CLI).
 * Usage: C:\xampp\php\php.exe X:\Vibe\Birthday\tests\smoke.php
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

$ok = true;

function check(string $label, bool $passed, string $extra = ''): void
{
    echo ($passed ? '  [ok]   ' : '  [FAIL] ') . $label . ($extra !== '' ? " — {$extra}" : '') . PHP_EOL;
    if (!$passed) {
        $GLOBALS['ok'] = false;
    }
}

echo "Birthday Surprise — smoke test\n==============================\n";

check('Config file present', is_file(BASE_PATH . '/config/app.php'));

try {
    $version = App\Core\Database::scalar('SELECT VERSION()');
    check('Database connection', true, (string) $version);

    $admins = (int) App\Core\Database::scalar('SELECT COUNT(*) FROM admins');
    check('Seed data (admins)', $admins > 0, $admins . ' admin account(s)');

    $settings = (int) App\Core\Database::scalar("SELECT COUNT(*) FROM app_settings");
    check('Seed data (app_settings)', $settings > 0, $settings . ' settings');
} catch (Throwable $e) {
    check('Database connection', false, $e->getMessage());
}

check('Storage writable', is_writable(BASE_PATH . '/storage/logs'));

echo $GLOBALS['ok'] ? "\nAll good 🎉\n" : "\nProblems found — see above.\n";
exit($GLOBALS['ok'] ? 0 : 1);
