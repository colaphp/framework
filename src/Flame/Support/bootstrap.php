<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Flame\Config\Config;
use Flame\Foundation\Configuration\Middleware;
use Flame\Routing\Route;
use Flame\Support\Facade\Log;

$worker = $worker ?? null;

set_error_handler(function ($level, $message, $file = '', $line = 0) {
    if (error_reporting() & $level) {
        throw new ErrorException($message, 0, $level, $file, $line);
    }
});

if ($worker) {
    register_shutdown_function(function ($startTime) {
        if (time() - $startTime <= 0.1) {
            sleep(1);
        }
    }, time());
}

if (class_exists('Dotenv\Dotenv') && file_exists(base_path().'/.env')) {
    if (method_exists('Dotenv\Dotenv', 'createUnsafeMutable')) {
        Dotenv::createUnsafeMutable(base_path(false))->load();
    } else {
        Dotenv::createMutable(base_path(false))->load();
    }
}

Config::clear();
Config::load(config_path());
foreach (config('autoload.files', []) as $file) {
    include_once $file;
}

Middleware::load(['__static__' => config('static.middleware', [])]);
if (class_exists('App\Http\Kernel')) {
    Middleware::load(['' => \App\Http\Kernel::$middleware]);
    Middleware::load(\App\Http\Kernel::$middlewareGroups);
}

foreach (config('app.providers', []) as $className) {
    if (! class_exists($className)) {
        $log = "Warning: Class $className setting in config/bootstrap.php not found\r\n";
        echo $log;
        Log::error($log);

        continue;
    }
    /** @var \Flame\Contracts\Bootstrap $className */
    $className::start($worker);
}

$apiRoutes = glob(app_path('API/*/Routes'), GLOB_ONLYDIR);
Route::load([base_path('routes'), ...$apiRoutes]);
