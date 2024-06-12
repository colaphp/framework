<?php

declare(strict_types=1);

use Flame\Container\Container;
use Flame\Foundation\App;
use Flame\Http\Request;
use Flame\Http\Response;
use Flame\Routing\Route;
use Flame\Support\Facade\View;
use Flame\Translation\Translation;
use Workerman\Worker;

defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__, 6));

function run_path(string $path = ''): string
{
    static $runPath = '';
    if (! $runPath) {
        $runPath = is_phar() ? dirname(Phar::running(false)) : BASE_PATH;
    }

    return path_combine($runPath, $path);
}

function base_path($path = ''): string
{
    if ($path === false) {
        return run_path();
    }

    return path_combine(BASE_PATH, $path);
}

function app_path(string $path = ''): string
{
    return path_combine(BASE_PATH.DIRECTORY_SEPARATOR.'app', $path);
}

function public_path(string $path = ''): string
{
    static $publicPath = '';
    if (! $publicPath) {
        $publicPath = \config('app.public_path') ?: run_path('public');
    }

    return path_combine($publicPath, $path);
}

function config_path(string $path = ''): string
{
    return path_combine(BASE_PATH.DIRECTORY_SEPARATOR.'config', $path);
}

function runtime_path(string $path = ''): string
{
    static $runtimePath = '';
    if (! $runtimePath) {
        $runtimePath = \config('app.runtime_path') ?: run_path('runtime');
    }

    return path_combine($runtimePath, $path);
}

function path_combine(string $front, string $back): string
{
    return $front.($back ? (DIRECTORY_SEPARATOR.ltrim($back, DIRECTORY_SEPARATOR)) : $back);
}

function response(string $body = '', int $status = 200, array $headers = []): Response
{
    return new Response($status, $headers, $body);
}

function json($data, int $options = JSON_UNESCAPED_UNICODE): Response
{
    return new Response(200, ['Content-Type' => 'application/json'], json_encode($data, $options));
}

function xml($xml): Response
{
    if ($xml instanceof SimpleXMLElement) {
        $xml = $xml->asXML();
    }

    return new Response(200, ['Content-Type' => 'text/xml'], $xml);
}

function jsonp($data, string $callbackName = 'callback'): Response
{
    if (! is_scalar($data) && $data !== null) {
        $data = json_encode($data);
    }

    return new Response(200, [], $callbackName($data));
}

function redirect(string $location, int $status = 302, array $headers = []): Response
{
    $response = new Response($status, ['Location' => $location]);
    if (! empty($headers)) {
        $response->withHeaders($headers);
    }

    return $response;
}

function view(string $template, array $vars = [], ?string $app = null): Response
{
    return new Response(200, [], View::render($template, $vars, $app));
}

function request(): Request
{
    return App::request();
}

function config(?string $key = null, $default = null)
{
    return \Flame\Config\Config::get($key, $default);
}

function route(string $name, ...$parameters): string
{
    $route = Route::getByName($name);
    if (! $route) {
        return '';
    }

    if (! $parameters) {
        return $route->url();
    }

    if (is_array(current($parameters))) {
        $parameters = current($parameters);
    }

    return $route->url($parameters);
}

function session($key = null, $default = null)
{
    $session = \request()->session();
    if ($key === null) {
        return $session;
    }
    if (is_array($key)) {
        $session->put($key);

        return null;
    }
    if (strpos($key, '.')) {
        $keyArray = explode('.', $key);
        $value = $session->all();
        foreach ($keyArray as $index) {
            if (! isset($value[$index])) {
                return $default;
            }
            $value = $value[$index];
        }

        return $value;
    }

    return $session->get($key, $default);
}

function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
{
    $res = Translation::trans($id, $parameters, $domain, $locale);

    return $res === '' ? $id : $res;
}

function locale(?string $locale = null): string
{
    if (! $locale) {
        return Translation::getLocale();
    }
    Translation::setLocale($locale);

    return $locale;
}

function not_found(): Response
{
    return new Response(404, [], file_get_contents(public_path().'/404.html'));
}

function copy_dir(string $source, string $dest, bool $overwrite = false): void
{
    if (is_dir($source)) {
        if (! is_dir($dest)) {
            mkdir($dest);
        }
        $files = scandir($source);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                copy_dir("$source/$file", "$dest/$file", $overwrite);
            }
        }
    } elseif (file_exists($source) && ($overwrite || ! file_exists($dest))) {
        copy($source, $dest);
    }
}

function remove_dir(string $dir): bool
{
    if (is_link($dir) || is_file($dir)) {
        return unlink($dir);
    }
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        (is_dir("$dir/$file") && ! is_link($dir)) ? remove_dir("$dir/$file") : unlink("$dir/$file");
    }

    return rmdir($dir);
}

function worker_bind($worker, $class): void
{
    $callbackMap = [
        'onConnect',
        'onMessage',
        'onClose',
        'onError',
        'onBufferFull',
        'onBufferDrain',
        'onWorkerStop',
        'onWebSocketConnect',
        'onWorkerReload',
    ];
    foreach ($callbackMap as $name) {
        if (method_exists($class, $name)) {
            $worker->$name = [$class, $name];
        }
    }
    if (method_exists($class, 'onWorkerStart')) {
        call_user_func([$class, 'onWorkerStart'], $worker);
    }
}

function worker_start($processName, $config): void
{
    $worker = new Worker($config['listen'] ?? null, $config['context'] ?? []);
    $propertyMap = [
        'count',
        'user',
        'group',
        'reloadable',
        'reusePort',
        'transport',
        'protocol',
    ];
    $worker->name = $processName;
    foreach ($propertyMap as $property) {
        if (isset($config[$property])) {
            $worker->$property = $config[$property];
        }
    }

    $worker->onWorkerStart = function ($worker) use ($config) {
        require_once __DIR__.'/bootstrap.php';
        if (isset($config['handler'])) {
            if (! class_exists($config['handler'])) {
                echo "process error: class {$config['handler']} not exists\r\n";

                return;
            }

            $instance = Container::make($config['handler'], $config['constructor'] ?? []);
            worker_bind($worker, $instance);
        }
    };
}

function get_realpath(string $filePath): string
{
    if (str_starts_with($filePath, 'phar://')) {
        return $filePath;
    } else {
        return realpath($filePath);
    }
}

function is_phar(): bool
{
    return class_exists(Phar::class, false) && Phar::running();
}

function cpu_count(): int
{
    // Windows does not support the number of processes setting.
    if (DIRECTORY_SEPARATOR === '\\') {
        return 1;
    }
    $count = 4;
    if (is_callable('shell_exec')) {
        if (strtolower(PHP_OS) === 'darwin') {
            $count = (int) shell_exec('sysctl -n machdep.cpu.core_count');
        } else {
            $count = (int) shell_exec('nproc');
        }
    }

    return $count > 0 ? $count : 4;
}

function input(?string $param = null, $default = null)
{
    return is_null($param) ? request()->all() : request()->input($param, $default);
}

require dirname(__DIR__).'/Foundation/helpers.php';
