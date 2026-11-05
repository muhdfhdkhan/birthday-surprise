<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Models\Profile;

// WIPE ALL demo/test profiles (id >= 1). On a brand-new install this is a
// no-op; after a smoke run it removes every created profile including the
// rows + media folders + analytics logs that cascade with them.
foreach (Database::all('SELECT id, slug FROM birthday_profiles') as $p) {
    Profile::destroy((int) $p['id']);
    echo 'deleted p' . $p['id'] . ' ' . $p['slug'] . PHP_EOL;
}
echo Database::query('DELETE FROM activity_logs')->rowCount() . " logs cleared\n";
echo Database::query('DELETE FROM visitor_responses')->rowCount() . " responses cleared\n";
echo Database::query('DELETE FROM access_requests')->rowCount() . " requests cleared\n";
echo Database::query('DELETE FROM access_grants')->rowCount() . " grants cleared\n";
echo Database::query('DELETE FROM rate_limits')->rowCount() . " rate rows cleared\n";
