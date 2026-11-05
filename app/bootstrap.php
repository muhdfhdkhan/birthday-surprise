<?php
/**
 * Application bootstrap: config, error handling, autoloading, session.
 * Included by public/index.php (web) and by CLI entry points (cron/tests).
 */

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!is_file($configFile = BASE_PATH . '/config/app.php')) {
    /* Not configured yet → send the browser to the one-time installer. */
    if (PHP_SAPI !== 'cli' && is_file(BASE_PATH . '/public/install.php')) {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        header('Location: ' . ($base === '' ? '' : $base) . '/install.php');
        exit;
    }
    if (PHP_SAPI === 'cli') {
        exit('Configuration missing: open public/install.php in a browser to install, '
            . "or copy config/app.example.php to config/app.php manually.\n");
    }
    http_response_code(500);
    exit('Configuration missing.');
}

/** @var array<string,mixed> $config */
$config = require $configFile;

error_reporting(E_ALL);
ini_set('display_errors', $config['debug'] ? '1' : '0');
ini_set('default_charset', 'UTF-8');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/php-error.log');

date_default_timezone_set($config['timezone'] ?? 'UTC');

require BASE_PATH . '/app/helpers.php';

// PSR-4 style autoloader: App\Core\Database -> app/Core/Database.php
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

App\Core\Session::start();

// Turn uncaught exceptions into a friendly (cat-style) 500 page.
set_exception_handler(static function (Throwable $e): void {
    error_log('[exception] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    if (config('debug')) {
        echo '<pre style="padding:24px;font:14px monospace;color:#3A2B4D;background:#FFF8F3">'
            . e('[ Birthday Surprise ] ' . $e->getMessage())
            . '</pre>';
        return;
    }
    App\Core\View::render('errors/500', ['title' => 'Something broke']);
});
