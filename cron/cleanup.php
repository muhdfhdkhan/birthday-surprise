<?php
/**
 * Cleanup cron (phase 1: rate_limits pruning).
 * Usage (CLI):
 *   C:\xampp\php\php.exe X:\Vibe\Birthday\cron\cleanup.php
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

use App\Services\RateLimiter;

if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

echo '[cleanup] ' . date('c') . PHP_EOL;
$pruned = RateLimiter::prune();
echo "[cleanup] rate_limits pruned: {$pruned}" . PHP_EOL;

// Later phases: expired access grants, log retention purge, orphaned uploads.
