<?php

namespace Cola\Foundation;

use Closure;
use Cola\Config\Config;
use Cola\Foundation\Exception\ExceptionHandler;
use Cola\Foundation\Exception\ExceptionHandlerInterface;
use Cola\Http\Middleware\Middleware;
use Cola\Http\Request;
use Cola\Http\Response;
use Cola\Routing\BaseRoute;
use Cola\Routing\Route;
use Cola\Support\Str;
use Exception;
use FastRoute\Dispatcher;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Throwable;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

/**
 * Class App
 * @package Cola\Foundation
 */
class App
{
    /**
     * @var bool
     */
    protected static $_supportStaticFiles = true;

    /**
     * @var bool
     */
    protected static $_supportPHPFiles = false;

    /**
     * @var array
     */
    protected static $_callbacks = [];

    /**
     * @var Worker
     */
    protected static $_worker = null;

    /**
     * @var ContainerInterface
     */
    protected static $_container = null;

    /**
     * @var Logger
     */
    protected static $_logger = null;

    /**
     * @var string
     */
    protected static $_appPath = '';

    /**
     * @var string
     */
    protected static $_publicPath = '';

    /**
     * @var string
     */
    protected static $_configPath = '';

    /**
     * @var TcpConnection
     */
    protected static $_connection = null;

    /**
     * @var Request
     */
    protected static $_request = null;

    /**
     * @var int
     */
    protected static $_gracefulStopTimer = null;

    /**
     * App constructor.
     * @param Worker $worker
     * @param $container
     * @param $logger
     * @param $app_path
     * @param $public_path
     */
    public function __construct(Worker $worker, $container, $logger, $app_path, $public_path)
    {
        static::$_worker = $worker;
        static::$_container = $container;
        static::$_logger = $logger;
        static::$_publicPath = $public_path;
        static::$_appPath = realpath($app_path);
        static::$_supportStaticFiles = Config::get('static.enable', true);
        static::$_supportPHPFiles = Config::get('app.support_php_files', false);
    }

    /**
     * @param TcpConnection $connection
     * @param Request $request
     * @return null
     */
    public function onMessage(TcpConnection $connection, $request)
    {
        try {
            static::$_request = $request;
            static::$_connection = $connection;
            $path = $request->path();
            $key = $request->method() . $path;

            if (isset(static::$_callbacks[$key])) {
                [$callback, $request->app, $request->controller, $request->action, $request->route] = static::$_callbacks[$key];
                static::send($connection, $callback($request), $request);
                return null;
            }

            if (static::unsafeUri($connection, $path, $request)) {
                return null;
            }

            if (static::findFile($connection, $path, $key, $request)) {
                return null;
            }

            if (static::findRoute($connection, $path, $key, $request)) {
                return null;
            }

            $controller_and_action = static::parseControllerAction($path);
            if (!$controller_and_action || Route::hasDisableDefaultRoute()) {
                $callback = static::getFallback();
                $request->app = $request->controller = $request->action = '';
                static::send($connection, $callback($request), $request);
                return null;
            }
            $app = $controller_and_action['app'];
            $controller = $controller_and_action['controller'];
            $action = $controller_and_action['action'];
            $callback = static::getCallback($app, [$controller_and_action['instance'], $action]);
            static::$_callbacks[$key] = [$callback, $app, $controller, $action, null];
            [$callback, $request->app, $request->controller, $request->action, $request->route] = static::$_callbacks[$key];
            static::send($connection, $callback($request), $request);
        } catch (Throwable $e) {
            static::send($connection, static::exceptionResponse($e, $request), $request);
        }
        return null;
    }

    /**
     * @param $connection
     * @param $path
     * @param $request
     * @return bool
     */
    protected static function unsafeUri($connection, $path, $request)
    {
        if (str_contains($path, '/../') || str_contains($path, "\\") || str_contains($path, "\0")) {
            $callback = static::getFallback();
            $request->app = $request->controller = $request->action = '';
            static::send($connection, $callback($request), $request);
            return true;
        }
        return false;
    }

    /**
     * @return Closure
     */
    protected static function getFallback()
    {
        // when route, controller and action not found, try to use Route::fallback
        return Route::getFallback() ?: function () {
            return new Response(404, [], file_get_contents(static::$_publicPath . '/404.html'));
        };
    }

    /**
     * @param Throwable $e
     * @param $request
     * @return string|Response
     */
    protected static function exceptionResponse(Throwable $e, $request)
    {
        try {
            $app = $request->app ?: '';
            $exception_config = Config::get('exception');
            $default_exception = $exception_config[''] ?? ExceptionHandler::class;
            $exception_handler_class = $exception_config[$app] ?? $default_exception;

            /** @var ExceptionHandlerInterface $exception_handler */
            $exception_handler = static::$_container->make($exception_handler_class, [
                'logger' => static::$_logger,
                'debug' => Config::get('app.debug')
            ]);
            $exception_handler->report($e);
            $response = $exception_handler->render($request, $e);
            return $response;
        } catch (Throwable $e) {
            return Config::get('app.debug') ? (string)$e : $e->getMessage();
        }
    }

    /**
     * @param $app
     * @param $call
     * @param null $args
     * @param bool $with_global_middleware
     * @param BaseRoute $route
     * @return Closure|mixed
     */
    protected static function getCallback($app, $call, $args = null, $with_global_middleware = true, $route = null)
    {
        $args = $args === null ? null : array_values($args);
        $middlewares = [];
        if ($route) {
            $route_middlewares = array_reverse($route->getMiddleware());
            foreach ($route_middlewares as $class_name) {
                $middlewares[] = [App::container()->get($class_name), 'process'];
            }
        }
        $middlewares = array_merge($middlewares, Middleware::getMiddleware($app, $with_global_middleware));

        if ($middlewares) {
            $callback = array_reduce($middlewares, function ($carry, $pipe) {
                return function ($request) use ($carry, $pipe) {
                    return $pipe($request, $carry);
                };
            }, function ($request) use ($call, $args) {

                try {
                    if ($args === null) {
                        $response = $call($request);
                    } else {
                        $response = $call($request, ...$args);
                    }
                } catch (Throwable $e) {
                    return static::exceptionResponse($e, $request);
                }
                if (is_scalar($response) || null === $response) {
                    $response = new Response(200, [], $response);
                }
                return $response;
            });
        } else {
            if ($args === null) {
                $callback = $call;
            } else {
                $callback = function ($request) use ($call, $args) {
                    return $call($request, ...$args);
                };
            }
        }
        return $callback;
    }

    /**
     * @return ContainerInterface
     */
    public static function container()
    {
        return static::$_container;
    }

    /**
     * @return Request
     */
    public static function request()
    {
        return static::$_request;
    }

    /**
     * @return TcpConnection
     */
    public static function connection()
    {
        return static::$_connection;
    }

    /**
     * @return Worker
     */
    public static function worker()
    {
        return static::$_worker;
    }

    /**
     * @param $connection
     * @param $path
     * @param $key
     * @param Request $request
     * @return bool
     */
    protected static function findRoute($connection, $path, $key, Request $request)
    {
        $ret = Route::dispatch($request->method(), $path);
        if ($ret[0] === Dispatcher::FOUND) {
            $ret[0] = 'route';
            $callback = $ret[1]['callback'];
            $route = $ret[1]['route'];
            $app = $controller = $action = '';
            $args = !empty($ret[2]) ? $ret[2] : null;
            if (is_array($callback) && isset($callback[0]) && $controller = get_class($callback[0])) {
                $app = static::getAppByController($controller);
                $action = static::getRealMethod($controller, $callback[1]) ?? '';
            }
            $callback = static::getCallback($app, $callback, $args, true, $route);
            static::$_callbacks[$key] = [$callback, $app, $controller ? $controller : '', $action, $route];
            [$callback, $request->app, $request->controller, $request->action, $request->route] = static::$_callbacks[$key];
            static::send($connection, $callback($request), $request);
            if (count(static::$_callbacks) > 1024) {
                static::clearCache();
            }
            return true;
        }
        return false;
    }

    /**
     * @param $connection
     * @param $path
     * @param $key
     * @param $request
     * @return bool
     */
    protected static function findFile($connection, $path, $key, $request)
    {
        $public_dir = static::$_publicPath;
        $file = "$public_dir/$path";

        if (!is_file($file)) {
            return false;
        }

        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            if (!static::$_supportPHPFiles) {
                return false;
            }
            static::$_callbacks[$key] = [function ($request) use ($file) {
                return static::execPhpFile($file);
            }, '', '', '', null];
            [$callback, $request->app, $request->controller, $request->action, $request->route] = static::$_callbacks[$key];
            static::send($connection, static::execPhpFile($file), $request);
            return true;
        }

        if (!static::$_supportStaticFiles) {
            return false;
        }

        static::$_callbacks[$key] = [static::getCallback('__static__', function ($request) use ($file) {
            clearstatcache(true, $file);
            if (!is_file($file)) {
                $callback = static::getFallback();
                return $callback($request);
            }
            return (new Response())->file($file);
        }, null, false), '', '', '', null];
        [$callback, $request->app, $request->controller, $request->action, $request->route] = static::$_callbacks[$key];
        static::send($connection, $callback($request), $request);
        return true;
    }

    /**
     * @param TcpConnection $connection
     * @param $response
     * @param Request $request
     */
    protected static function send(TcpConnection $connection, $response, Request $request)
    {
        $keep_alive = $request->header('connection');
        static::$_request = static::$_connection = null;
        if (($keep_alive === null && $request->protocolVersion() === '1.1')
            || $keep_alive === 'keep-alive' || $keep_alive === 'Keep-Alive'
        ) {
            $connection->send($response);
            return;
        }
        $connection->close($response);
    }

    /**
     * @param $path
     * @return array|bool
     */
    protected static function parseControllerAction($path)
    {
        $default_module = config('app.default_module', 'Index');
        $suffix = config('app.controller_suffix', '');
        $path_explode = explode('/', trim($path, '/'));
        $app = !empty($path_explode[0]) ? Str::studly($path_explode[0]) : $default_module;
        $controller = isset($path_explode[1]) ? Str::studly($path_explode[1]) : 'Index';
        $action = isset($path_explode[2]) ? Str::camel($path_explode[2]) : 'index';

        if (isset($path_explode[2])) {
            $controller_class = 'App\\Http\\Controllers\\' . $app . '\\' . $controller . $suffix;
            if ($controller_action = static::getControllerAction($controller_class, $action)) {
                return $controller_action;
            }
        }

        $controller = $app;
        $action = isset($path_explode[1]) ? Str::camel($path_explode[1]) : 'index';

        $controller_class = 'App\\Http\\Controllers\\' . $default_module . '\\' . $controller . $suffix;
        if ($controller_action = static::getControllerAction($controller_class, $action)) {
            return $controller_action;
        }

        $controller = isset($path_explode[1]) ? Str::studly($path_explode[1]) : 'Index';
        $action = isset($path_explode[2]) ? Str::camel($path_explode[2]) : 'index';
        $controller_class = 'App\\Http\\Controllers\\' . $app . '\\' . $controller . $suffix;
        if ($controller_action = static::getControllerAction($controller_class, $action)) {
            return $controller_action;
        }
        return false;
    }

    /**
     * @param $controller_class
     * @param $action
     * @return array|false
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    protected static function getControllerAction($controller_class, $action)
    {
        if (static::loadController($controller_class) && ($controller_class = (new ReflectionClass($controller_class))->name) && is_callable([$instance = static::$_container->get($controller_class), $action])) {
            return [
                'app' => static::getAppByController($controller_class),
                'controller' => $controller_class,
                'action' => static::getRealMethod($controller_class, $action),
                'instance' => $instance,
            ];
        }
        return false;
    }

    /**
     * @param $controller_class
     * @return bool
     */
    protected static function loadController($controller_class)
    {
        static $controller_files = [];
        if (empty($controller_files)) {
            $app_path = static::$_appPath;
            $dir_iterator = new RecursiveDirectoryIterator($app_path);
            $iterator = new RecursiveIteratorIterator($dir_iterator);
            $app_base_path_length = strrpos($app_path, DIRECTORY_SEPARATOR) + 1;

            foreach ($iterator as $spl_file) {
                $file = (string)$spl_file;
                if (is_dir($file) || false === strpos($file, '/Controllers/') || $spl_file->getExtension() !== 'php') {
                    continue;
                }
                $controller_files[$file] = str_replace(DIRECTORY_SEPARATOR, "\\", strtolower(substr(substr($file, $app_base_path_length), 0, -4)));
            }
        }

        if (class_exists($controller_class)) {
            return true;
        }

        $controller_class = strtolower($controller_class);
        if ($controller_class[0] === "\\") {
            $controller_class = substr($controller_class, 1);
        }

        foreach ($controller_files as $real_path => $class_name) {
            if ($class_name === $controller_class) {
                require_once $real_path;
                if (class_exists($controller_class, false)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param $controller_calss
     * @return string
     */
    protected static function getAppByController($controller_calss)
    {
        if ($controller_calss[0] === '\\') {
            $controller_calss = substr($controller_calss, 1);
        }
        $tmp = explode('\\', $controller_calss, 5);
        if (!isset($tmp[3]) || str_contains($tmp[3], 'Controller')) {
            return '';
        }
        return $tmp[3];
    }

    /**
     * @param $file
     * @return string
     */
    public static function execPhpFile($file)
    {
        ob_start();
        // Try to include php file.
        try {
            include $file;
        } catch (Exception $e) {
            echo $e;
        }
        return ob_get_clean();
    }

    /**
     * Clear cache.
     */
    public static function clearCache()
    {
        static::$_callbacks = [];
    }

    /**
     * @param $class
     * @param $method
     * @return string
     */
    protected static function getRealMethod($class, $method)
    {
        $method = strtolower($method);
        $methods = get_class_methods($class);
        foreach ($methods as $candidate) {
            if (strtolower($candidate) === $method) {
                return $candidate;
            }
        }
        return $method;
    }
}
