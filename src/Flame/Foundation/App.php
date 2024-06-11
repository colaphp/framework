<?php

declare(strict_types=1);

namespace Flame\Foundation;

use Closure;
use Exception;
use FastRoute\Dispatcher;
use Flame\Config\Config;
use Flame\Container\Container;
use Flame\Foundation\Configuration\Middleware;
use Flame\Routing\Route;
use Flame\Support\Arr;
use Flame\Support\Facade\Log;
use Flame\Support\Str;
use Flame\Support\Util;
use InvalidArgumentException;
use Monolog\Logger;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use Reflector;
use Flame\Contracts\Middleware as MiddlewareContract;
use Throwable;
use Flame\Foundation\Exception\ExceptionHandler;
use Flame\Foundation\Contract\ExceptionHandlerInterface;
use Flame\Http\Request;
use Flame\Http\Response;
use Flame\Routing\RouteItem as RouteObject;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http;
use Workerman\Worker;

class App
{
    protected static array $callbacks = [];

    protected static ?Worker $worker = null;

    protected static ?Logger $logger = null;

    protected static string $appPath = '';

    protected static string $publicPath = '';

    protected static string $requestClass = '';

    public function __construct(string $appPath, string $publicPath)
    {
        static::$requestClass = Request::class;
        static::$logger = Log::channel('default');
        static::$appPath = $appPath;
        static::$publicPath = $publicPath;
    }

    public function onMessage($connection, $request): null
    {
        try {
            Context::set(Request::class, $request);

            $path = $request->path();
            $key = $request->method().$path;
            if (isset(static::$callbacks[$key])) {
                [$callback, $request->app, $request->controller, $request->action, $request->route] = static::$callbacks[$key];
                static::send($connection, $callback($request), $request);

                return null;
            }

            if (
                static::unsafeUri($connection, $path, $request) ||
                static::findFile($connection, $path, $key, $request) ||
                static::findRoute($connection, $path, $key, $request)
            ) {
                return null;
            }

            $controllerAndAction = static::parseControllerAction($path);
            if (! $controllerAndAction) {
                $callback = static::getFallback();
                $request->app = $request->controller = $request->action = '';
                static::send($connection, $callback($request), $request);

                return null;
            }
            $app = $controllerAndAction['app'];
            $controller = $controllerAndAction['controller'];
            $action = $controllerAndAction['action'];
            $callback = static::getCallback($app, [$controller, $action]);
            static::collectCallbacks($key, [$callback, $app, $controller, $action, null]);
            [$callback, $request->app, $request->controller, $request->action, $request->route] = static::$callbacks[$key];
            static::send($connection, $callback($request), $request);
        } catch (Throwable $e) {
            static::send($connection, static::exceptionResponse($e, $request), $request);
        }

        return null;
    }

    public function onWorkerStart($worker): void
    {
        static::$worker = $worker;
        Http::requestClass(static::$requestClass);
    }

    protected static function collectCallbacks(string $key, array $data): void
    {
        static::$callbacks[$key] = $data;
        if (count(static::$callbacks) >= 1024) {
            unset(static::$callbacks[key(static::$callbacks)]);
        }
    }

    protected static function unsafeUri(TcpConnection $connection, string $path, $request): bool
    {
        if (
            ! $path ||
            str_contains($path, '..') ||
            str_contains($path, '\\') ||
            str_contains($path, "\0")
        ) {
            $callback = static::getFallback();
            $request->app = $request->controller = $request->action = '';
            static::send($connection, $callback($request), $request);

            return true;
        }

        return false;
    }

    protected static function getFallback(): Closure
    {
        // when route, controller and action not found, try to use Route::fallback
        return Route::getFallback() ?: function () {
            try {
                $notFoundContent = file_get_contents(static::$publicPath.'/404.html');
            } catch (Throwable $e) {
                $notFoundContent = '404 Not Found';
            }

            return new Response(404, [], $notFoundContent);
        };
    }

    protected static function exceptionResponse(Throwable $e, $request): Response
    {
        try {
            $app = $request->app ?: '';
            $exceptionConfig = static::config('exception');
            $defaultException = $exceptionConfig[''] ?? ExceptionHandler::class;
            $exceptionHandlerClass = $exceptionConfig[$app] ?? $defaultException;

            /** @var ExceptionHandlerInterface $exceptionHandler */
            $exceptionHandler = static::container()->make($exceptionHandlerClass, [
                'logger' => static::$logger,
                'debug' => static::config( 'app.debug'),
            ]);
            $exceptionHandler->report($e);
            $response = $exceptionHandler->render($request, $e);
            $response->exception($e);

            return $response;
        } catch (Throwable $e) {
            $response = new Response(500, [], static::config('app.debug') ? (string) $e : $e->getMessage());
            $response->exception($e);

            return $response;
        }
    }

    /**
     * @throws Exception
     */
    protected static function getCallback(string $app, $call, ?array $args = null, bool $withGlobalMiddleware = true, ?RouteObject $route = null)
    {
        $args = $args === null ? null : array_values($args);
        $middlewares = [];
        if ($route) {
            $routeMiddlewares = $route->getMiddleware();
            foreach ($routeMiddlewares as $className) {
                $middlewares[] = [$className, 'process'];
            }
        }
        $middlewares = array_merge($middlewares, Middleware::getMiddleware($app, $withGlobalMiddleware));

        foreach ($middlewares as $key => $item) {
            $middleware = $item[0];
            if (is_string($middleware)) {
                $middleware = static::container()->get($middleware);
            } elseif ($middleware instanceof Closure) {
                $middleware = call_user_func($middleware, static::container());
            }
            if (! $middleware instanceof MiddlewareContract) {
                throw new InvalidArgumentException('Not support middleware type');
            }
            $middlewares[$key][0] = $middleware;
        }

        $needInject = static::isNeedInject($call, $args);
        if (is_array($call) && is_string($call[0])) {
            $controllerReuse = static::config('app.controller_reuse', true);
            if (! $controllerReuse) {
                if ($needInject) {
                    $call = function ($request, ...$args) use ($call) {
                        $call[0] = static::container()->make($call[0]);
                        $reflector = static::getReflector($call);
                        $args = static::resolveMethodDependencies($request, $args, $reflector);

                        return $call(...$args);
                    };
                    $needInject = false;
                } else {
                    $call = function ($request, ...$args) use ($call) {
                        $call[0] = static::container()->make($call[0]);

                        return $call($request, ...$args);
                    };
                }
            } else {
                $call[0] = static::container()->get($call[0]);
            }
        }

        if ($needInject) {
            $call = static::resolveInject($call);
        }

        if ($middlewares) {
            $callback = array_reduce($middlewares, function ($carry, $pipe) {
                return function ($request) use ($carry, $pipe) {
                    try {
                        return $pipe($request, $carry);
                    } catch (Throwable $e) {
                        return static::exceptionResponse($e, $request);
                    }
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
                if (! $response instanceof Response) {
                    if (! is_string($response)) {
                        $response = static::stringify($response);
                    }
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

    protected static function resolveInject($call): Closure
    {
        return function (Request $request, ...$args) use ($call) {
            $reflector = static::getReflector($call);
            $args = static::resolveMethodDependencies($request, $args, $reflector);

            return $call(...$args);
        };
    }

    /**
     * @throws Exception
     */
    protected static function isNeedInject($call, $args): bool
    {
        if (is_array($call) && ! method_exists($call[0], $call[1])) {
            return false;
        }
        $args = $args ?: [];
        $reflector = static::getReflector($call);
        $reflectionParameters = $reflector->getParameters();
        if (! $reflectionParameters) {
            return false;
        }
        $firstParameter = current($reflectionParameters);
        unset($reflectionParameters[key($reflectionParameters)]);
        $adaptersList = ['int', 'string', 'bool', 'array', 'object', 'float', 'mixed', 'resource'];
        foreach ($reflectionParameters as $parameter) {
            if ($parameter->hasType() && ! in_array($parameter->getType()->getName(), $adaptersList)) {
                return true;
            }
        }
        if (! $firstParameter->hasType()) {
            return count($args) > count($reflectionParameters);
        }

        if (! is_a(static::$requestClass, $firstParameter->getType()->getName())) {
            return true;
        }

        return false;
    }

    /**
     * @throws Exception
     */
    protected static function getReflector($call): Reflector
    {
        if ($call instanceof Closure || is_string($call)) {
            return new ReflectionFunction($call);
        }

        return new ReflectionMethod($call[0], $call[1]);
    }

    /**
     * @throws Exception
     */
    protected static function resolveMethodDependencies(Request $request, array $args, Reflector $reflector): array
    {
        // Specification parameter information
        $args = array_values($args);
        $parameters = [];
        // An array of reflection classes for loop parameters, with each $parameter representing a reflection object of parameters
        foreach ($reflector->getParameters() as $parameter) {
            // Parameter quota consumption
            if ($parameter->hasType()) {
                $name = $parameter->getType()->getName();
                switch ($name) {
                    case 'int':
                    case 'string':
                    case 'bool':
                    case 'array':
                    case 'object':
                    case 'float':
                    case 'mixed':
                    case 'resource':
                        goto _else;
                    default:
                        if (is_a($request, $name)) {
                            //Inject Request
                            $parameters[] = $request;
                        } else {
                            $parameters[] = static::container()->make($name);
                        }
                        break;
                }
            } else {
                _else:
                // The variable parameter
                if (key($args) !== null) {
                    $parameters[] = current($args);
                } else {
                    // Indicates whether the current parameter has a default value.  If yes, return true
                    $parameters[] = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
                }
                // Quota of consumption variables
                next($args);
            }
        }

        // Returns the result of parameters replacement
        return $parameters;
    }

    /**
     * @throws Exception
     */
    public static function container()
    {
        return Container::instance();
    }

    public static function request()
    {
        return Context::get(Request::class);
    }

    /**
     * Get worker.
     */
    public static function worker(): ?Worker
    {
        return static::$worker;
    }

    /**
     * @throws Exception
     */
    protected static function findRoute(TcpConnection $connection, string $path, string $key, $request): bool
    {
        $routeInfo = Route::dispatch($request->method(), $path);
        if ($routeInfo[0] === Dispatcher::FOUND) {
            $routeInfo[0] = 'route';
            $callback = $routeInfo[1]['callback'];
            $route = clone $routeInfo[1]['route'];
            $app = $controller = $action = '';
            $args = ! empty($routeInfo[2]) ? $routeInfo[2] : null;
            if ($args) {
                $route->setParams($args);
            }
            if (is_array($callback)) {
                $controller = $callback[0];
                $app = static::getAppByController($controller);
                $action = static::getRealMethod($controller, $callback[1]) ?? '';
            }
            $callback = static::getCallback($app, $callback, $args, true, $route);
            static::collectCallbacks($key, [$callback, $app, $controller ?: '', $action, $route]);
            [$callback, $request->app, $request->controller, $request->action, $request->route] = static::$callbacks[$key];
            static::send($connection, $callback($request), $request);

            return true;
        }

        return false;
    }

    /**
     * @throws Exception
     */
    protected static function findFile(TcpConnection $connection, string $path, string $key, $request): bool
    {
        if (preg_match('/%[0-9a-f]{2}/i', $path)) {
            $path = urldecode($path);
            if (static::unsafeUri($connection, $path, $request)) {
                return true;
            }
        }

        $pathExplodes = explode('/', trim($path, '/'));
        if (isset($pathExplodes[1]) && $pathExplodes[0] === 'app') {
            $publicDir = static::config('app.public_path');
            $path = substr($path, strlen("/app/$pathExplodes[1]/"));
        } else {
            $publicDir = static::$publicPath;
        }
        $file = "$publicDir/$path";
        if (! is_file($file)) {
            return false;
        }

        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            if (! static::config('app.support_php_files', false)) {
                return false;
            }
            static::collectCallbacks($key, [function () use ($file) {
                return static::execPhpFile($file);
            }, '', '', '', null]);
            [, $request->app, $request->controller, $request->action, $request->route] = static::$callbacks[$key];
            static::send($connection, static::execPhpFile($file), $request);

            return true;
        }

        if (! static::config('static.enable', false)) {
            return false;
        }

        static::collectCallbacks($key, [static::getCallback('__static__', function ($request) use ($file) {
            clearstatcache(true, $file);
            if (! is_file($file)) {
                $callback = static::getFallback();

                return $callback($request);
            }

            return (new Response())->file($file);
        }, null, false), '', '', '', null]);
        [$callback, $request->app, $request->controller, $request->action, $request->route] = static::$callbacks[$key];
        static::send($connection, $callback($request), $request);

        return true;
    }

    protected static function send($connection, $response, $request): void
    {
        $keepAlive = $request->header('connection');
        Context::destroy();
        if (($keepAlive === null && $request->protocolVersion() === '1.1')
            || $keepAlive === 'keep-alive' || $keepAlive === 'Keep-Alive'
        ) {
            $connection->send($response);

            return;
        }
        $connection->close($response);
    }

    /**
     * @throws Exception
     */
    protected static function parseControllerAction(string $path)
    {
        $path = str_replace(['-', '//'], ['', '/'], $path);
        static $cache = [];
        if (isset($cache[$path])) {
            return $cache[$path];
        }

        $pathExplode = $path ? explode('/', $path) : [];
        $pathExplode = Arr::where($pathExplode, function ($item, $key) {
           return $key > 0;
        });

        $action = 'index';
        if (! $controllerAction = static::guessControllerAction($pathExplode, $action)) {
            if (count($pathExplode) <= 1) {
                return false;
            }
            $action = end($pathExplode);
            unset($pathExplode[count($pathExplode) - 1]);
            $controllerAction = static::guessControllerAction($pathExplode, $action);
        }

        if ($controllerAction && ! isset($path[256])) {
            $cache[$path] = $controllerAction;
            if (count($cache) > 1024) {
                unset($cache[key($cache)]);
            }
        }

        return $controllerAction;
    }

    /**
     * @throws Exception
     */
    protected static function guessControllerAction($pathExplode, $action): false|array
    {
        $pathExplode = array_map(function ($item) {
            return Str::studly($item);
        }, $pathExplode);

        $suffix = Config::get("app.controller_suffix", '');

        $defaultModule = Config::get('app.default_module', '');
        $map[] = trim("\\App\\Http\\Controllers\\".$defaultModule.'\\'.implode('\\', $pathExplode), '\\');

        $map[] = trim("\\App\\Http\\Controllers\\".implode('\\', $pathExplode), '\\');
        foreach ($map as $item) {
            $map[] = $item.'\\Index';
        }

        foreach ($map as $controllerClass) {
            // Remove xx\xx\controller
            if (str_ends_with($controllerClass, '\\Controller')) {
                continue;
            }
            $controllerClass .= $suffix;
            if ($controllerAction = static::getControllerAction($controllerClass, $action)) {
                return $controllerAction;
            }
        }

        return false;
    }

    /**
     * @throws Exception
     */
    protected static function getControllerAction(string $controllerClass, string $action): false|array
    {
        // Disable calling magic methods
        if (str_starts_with($action, '__')) {
            return false;
        }

        if (($controllerClass = static::getController($controllerClass)) && ($action = static::getAction($controllerClass, $action))) {
            return [
                'app' => static::getAppByController($controllerClass),
                'controller' => $controllerClass,
                'action' => $action,
            ];
        }

        return false;
    }

    /**
     * @throws Exception
     */
    protected static function getController(string $controllerClass)
    {
        if (class_exists($controllerClass)) {
            return (new ReflectionClass($controllerClass))->name;
        }
        $explodes = explode('\\', strtolower(ltrim($controllerClass, '\\')));
        $basePath = static::$appPath;
        unset($explodes[0]);
        $fileName = array_pop($explodes).'.php';
        $found = true;
        foreach ($explodes as $pathSection) {
            if (! $found) {
                break;
            }
            $dirs = Util::scanDir($basePath, false);
            $found = false;
            foreach ($dirs as $name) {
                $path = "$basePath/$name";
                if (is_dir($path) && strtolower($name) === $pathSection) {
                    $basePath = $path;
                    $found = true;
                    break;
                }
            }
        }
        if (! $found) {
            return false;
        }
        foreach (scandir($basePath) ?: [] as $name) {
            if (strtolower($name) === $fileName) {
                require_once "$basePath/$name";
                if (class_exists($controllerClass, false)) {
                    return (new ReflectionClass($controllerClass))->name;
                }
            }
        }

        return false;
    }

    protected static function getAction(string $controllerClass, string $action)
    {
        $methods = get_class_methods($controllerClass);
        $lowerAction = strtolower($action);
        $found = false;
        foreach ($methods as $candidate) {
            if (strtolower($candidate) === $lowerAction) {
                $action = $candidate;
                $found = true;
                break;
            }
        }
        if ($found) {
            return $action;
        }
        // Action is not public method
        if (method_exists($controllerClass, $action)) {
            return false;
        }
        if (method_exists($controllerClass, '__call')) {
            return $action;
        }

        return false;
    }

    protected static function getAppByController(string $controllerClass): string
    {
        $defaultModule = Config::get('app.default_module', '');

        $controllerClass = trim($controllerClass, '\\');
        $tmp = explode('\\', $controllerClass, 5);

        $pos = 3;
        if (! isset($tmp[$pos]) || $tmp[$pos] === $defaultModule) {
            return '';
        }

        return strtolower($tmp[$pos]) === 'Controller' ? '' : $tmp[$pos];
    }

    public static function execPhpFile(string $file): false|string
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

    protected static function getRealMethod(string $class, string $method): string
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

    protected static function config(string $key, $default = null): mixed
    {
        return Config::get($key, $default);
    }

    protected static function stringify($data): string
    {
        $type = gettype($data);
        switch ($type) {
            case 'boolean':
                return $data ? 'true' : 'false';
            case 'NULL':
                return 'NULL';
            case 'array':
                return 'Array';
            case 'object':
                if (! method_exists($data, '__toString')) {
                    return 'Object';
                }
                return (string) $data;
            default:
                return (string) $data;
        }
    }
}
