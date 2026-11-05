<?php
/**
 * Birthday Surprise — front controller.
 * Every request inside /public that is not a real file is routed through here.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

$router = new App\Core\Router();
$router->register(require BASE_PATH . '/routes/web.php', 'web');
$router->register(require BASE_PATH . '/routes/api.php', 'api');
$router->dispatch();
