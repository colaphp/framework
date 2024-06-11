<?php

declare(strict_types=1);

namespace Flame\Routing;

use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;
use Flame\Routing\RouteItem as RouteObject;

use function FastRoute\simpleDispatcher;

class Route
{
    protected static ?Route $instance = null;

    protected static Dispatcher $dispatcher;

    protected static RouteCollector $collector;

    protected static array $fallback = [];

    protected static array $nameList = [];

    protected static string $groupPrefix = '';

    protected static array $disableDefaultRoute = [];

    protected static array $allRoutes = [];

    protected array $routes = [];

    protected array $children = [];

    public static function get(string $path, $callback): RouteObject
    {
        return static::addRoute('GET', $path, $callback);
    }

    public static function post(string $path, $callback): RouteObject
    {
        return static::addRoute('POST', $path, $callback);
    }

    public static function put(string $path, $callback): RouteObject
    {
        return static::addRoute('PUT', $path, $callback);
    }

    public static function patch(string $path, $callback): RouteObject
    {
        return static::addRoute('PATCH', $path, $callback);
    }

    public static function delete(string $path, $callback): RouteObject
    {
        return static::addRoute('DELETE', $path, $callback);
    }

    public static function head(string $path, $callback): RouteObject
    {
        return static::addRoute('HEAD', $path, $callback);
    }

    public static function options(string $path, $callback): RouteObject
    {
        return static::addRoute('OPTIONS', $path, $callback);
    }

    public static function any(string $path, $callback): RouteObject
    {
        return static::addRoute(['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'], $path, $callback);
    }

    public static function add($method, string $path, $callback): RouteObject
    {
        return static::addRoute($method, $path, $callback);
    }

    public static function group(callable|string $path, ?callable $callback = null): Route
    {
        if ($callback === null) {
            $callback = $path;
            $path = '';
        }
        $previousGroupPrefix = static::$groupPrefix;
        static::$groupPrefix = $previousGroupPrefix.$path;
        $previousInstance = static::$instance;
        $instance = static::$instance = new static;
        static::$collector->addGroup($path, $callback);
        static::$groupPrefix = $previousGroupPrefix;
        static::$instance = $previousInstance;
        $previousInstance?->addChild($instance);

        return $instance;
    }

    public static function resource(string $name, string $controller, array $options = []): void
    {
        $name = trim($name, '/');
        if (is_array($options) && ! empty($options)) {
            $diffOptions = array_diff($options, ['index', 'create', 'store', 'update', 'show', 'edit', 'destroy', 'recovery']);
            if (! empty($diffOptions)) {
                foreach ($diffOptions as $action) {
                    static::any("/$name/{$action}[/{id}]", [$controller, $action])->name("$name.{$action}");
                }
            }
            // 注册路由 由于顺序不同会导致路由无效 因此不适用循环注册
            if (in_array('index', $options)) {
                static::get("/$name", [$controller, 'index'])->name("$name.index");
            }
            if (in_array('create', $options)) {
                static::get("/$name/create", [$controller, 'create'])->name("$name.create");
            }
            if (in_array('store', $options)) {
                static::post("/$name", [$controller, 'store'])->name("$name.store");
            }
            if (in_array('update', $options)) {
                static::put("/$name/{id}", [$controller, 'update'])->name("$name.update");
            }
            if (in_array('patch', $options)) {
                static::patch("/$name/{id}", [$controller, 'patch'])->name("$name.patch");
            }
            if (in_array('show', $options)) {
                static::get("/$name/{id}", [$controller, 'show'])->name("$name.show");
            }
            if (in_array('edit', $options)) {
                static::get("/$name/{id}/edit", [$controller, 'edit'])->name("$name.edit");
            }
            if (in_array('destroy', $options)) {
                static::delete("/$name/{id}", [$controller, 'destroy'])->name("$name.destroy");
            }
            if (in_array('recovery', $options)) {
                static::put("/$name/{id}/recovery", [$controller, 'recovery'])->name("$name.recovery");
            }
        } else {
            //为空时自动注册所有常用路由
            if (method_exists($controller, 'index')) {
                static::get("/$name", [$controller, 'index'])->name("$name.index");
            }
            if (method_exists($controller, 'create')) {
                static::get("/$name/create", [$controller, 'create'])->name("$name.create");
            }
            if (method_exists($controller, 'store')) {
                static::post("/$name", [$controller, 'store'])->name("$name.store");
            }
            if (method_exists($controller, 'update')) {
                static::put("/$name/{id}", [$controller, 'update'])->name("$name.update");
            }
            if (method_exists($controller, 'patch')) {
                static::patch("/$name/{id}", [$controller, 'patch'])->name("$name.patch");
            }
            if (method_exists($controller, 'show')) {
                static::get("/$name/{id}", [$controller, 'show'])->name("$name.show");
            }
            if (method_exists($controller, 'edit')) {
                static::get("/$name/{id}/edit", [$controller, 'edit'])->name("$name.edit");
            }
            if (method_exists($controller, 'destroy')) {
                static::delete("/$name/{id}", [$controller, 'destroy'])->name("$name.destroy");
            }
            if (method_exists($controller, 'recovery')) {
                static::put("/$name/{id}/recovery", [$controller, 'recovery'])->name("$name.recovery");
            }
        }
    }

    public static function getRoutes(): array
    {
        return static::$allRoutes;
    }

    public static function disableDefaultRoute($plugin = ''): void
    {
        static::$disableDefaultRoute[$plugin] = true;
    }

    public static function hasDisableDefaultRoute(string $plugin = ''): bool
    {
        return static::$disableDefaultRoute[$plugin] ?? false;
    }

    public function middleware($middleware): Route
    {
        foreach ($this->routes as $route) {
            $route->middleware($middleware);
        }
        foreach ($this->getChildren() as $child) {
            $child->middleware($middleware);
        }

        return $this;
    }

    public function collect(RouteObject $route): void
    {
        $this->routes[] = $route;
    }

    public static function setByName(string $name, RouteObject $instance): void
    {
        static::$nameList[$name] = $instance;
    }

    public static function getByName(string $name): ?RouteObject
    {
        return static::$nameList[$name] ?? null;
    }

    public function addChild(Route $route): void
    {
        $this->children[] = $route;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public static function dispatch(string $method, string $path): array
    {
        return static::$dispatcher->dispatch($method, $path);
    }

    public static function convertToCallable(string $path, $callback)
    {
        if (is_string($callback) && strpos($callback, '@')) {
            $callback = explode('@', $callback, 2);
        }

        if (! is_array($callback)) {
            if (! is_callable($callback)) {
                $callStr = is_scalar($callback) ? $callback : 'Closure';
                echo "Route $path $callStr is not callable\n";

                return false;
            }
        } else {
            $callback = array_values($callback);
            if (! isset($callback[1]) || ! class_exists($callback[0]) || ! method_exists($callback[0], $callback[1])) {
                echo "Route $path ".json_encode($callback)." is not callable\n";

                return false;
            }
        }

        return $callback;
    }

    protected static function addRoute(array|string $methods, string $path, $callback): RouteObject
    {
        if (! is_array($methods)) {
            $methods = [$methods];
        }

        $route = new RouteObject($methods, static::$groupPrefix.$path, $callback);
        static::$allRoutes[] = $route;

        if ($callback = static::convertToCallable($path, $callback)) {
            static::$collector->addRoute($methods, $path, ['callback' => $callback, 'route' => $route]);
        }
        if (static::$instance) {
            static::$instance->collect($route);
        }

        return $route;
    }

    public static function load($paths): void
    {
        if (! is_array($paths)) {
            return;
        }
        static::$dispatcher = simpleDispatcher(function (RouteCollector $route) use ($paths) {
            Route::setCollector($route);
            foreach ($paths as $configPath) {
                $routeConfigFile = $configPath.'/route.php';
                if (is_file($routeConfigFile)) {
                    require_once $routeConfigFile;
                }
            }
        });
    }

    public static function setCollector(RouteCollector $route): void
    {
        static::$collector = $route;
    }

    public static function fallback(callable $callback, string $plugin = ''): void
    {
        static::$fallback[$plugin] = $callback;
    }

    public static function getFallback(string $plugin = ''): ?callable
    {
        return static::$fallback[$plugin] ?? null;
    }
}
