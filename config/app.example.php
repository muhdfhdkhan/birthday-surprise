<?php
/**
 * Birthday Surprise — application configuration.
 * Copy this file to config/app.php and adjust values per environment.
 */

declare(strict_types=1);

return [
    // 'local' shows errors + dev hints; 'production' hides them.
    'env'      => 'local',
    'debug'    => true,

    'name'     => 'Birthday Surprise',
    'timezone' => 'UTC',

    // Database — XAMPP defaults (root, empty password).
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'birthday_surprise',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    // Sessions / admin idle timeout.
    'session' => [
        'name'         => 'BIRTHDAY_SID',
        'idle_minutes' => 60,
    ],

    // Brute-force protection for the admin login.
    'security' => [
        'login_max_attempts'   => 5,
        'login_window_minutes' => 15,
    ],
];
