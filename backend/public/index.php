<?php
declare(strict_types=1);

use App\Kernel;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require_once dirname(__DIR__).'/vendor/autoload.php';

if ($_SERVER['APP_DEBUG'] ?? true) {
    umask(0000);
    Debug::enable();
}

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool)($_SERVER['APP_DEBUG'] ?? true));
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
