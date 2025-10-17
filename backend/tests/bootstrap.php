<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = $_SERVER['APP_DEBUG'] ?? '1';

$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile)) {
    (new Dotenv())->usePutenv(true)->bootEnv($envFile, 'test');
}
