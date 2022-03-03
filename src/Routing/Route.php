<?php

namespace Swift\Routing;

use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;
use Psr\Container\ContainerInterface;
use Swift\Foundation\App;
use function FastRoute\simpleDispatcher;

/**
 * Class Route
 * @package Swift\Routing
 */
class Route
{
    /**
     * @var ContainerInterface
     */
    protected static $_container = null;

    /**
     * @var Route
     */
    protected static $_instance = null;

    /**
     * @var GroupCountBased
     */
    protected static $_dispatcher = null;

    /**
     * @var RouteCollector
     */
    protected static $_collector = null;

    /**
     * @var bool
     */
    protected static $_hasRoute = false;

    /**
     * @var null|callable
     */
    protected static $_fallback = null;

    /**
     * @var array
     */
    protected static $_nameList = [];

    /**
     * @var string
     */
    protected static $_groupPrefix = '';

    /**
     * @var bool
     */
    protected static $_disableDefaultRoute = false;

    /**
     * @var BaseRoute[]
     */
    protected static $_allRoutes = [];

    /**
     * @var BaseRoute[]
     */
    protected $_routes = [];

    /**
     * @param $path
     * @param $callback
     * @return BaseRoute
     */
    public static function get($path, $callback)
    {
        return static::addRoute('GET', $path, $callback);
    }

    /**
     * @param $path
     * @param $callback
     * @return BaseRoute
     */
    public static function post($path, $callback)
    {
        return static::addRoute('POST', $path, $callback);
    }

    /**
     * @param $path
     * @param $callback
     * @return BaseRoute
     */
    public static function put($path, $callback)
    {
        return static::addRoute('PUT', $path, $callback);
    }

    /**
     * @param $path
     * @param $callback
     * @return BaseRoute
     */
    public static function patch($path, $callback)
    {
        return static::addRoute('PATCH', $path, $callback);
    }

    /**
     * @param $path
     * @param $callback
     * @return BaseRoute
     */
    public static function delete($path, $callback)
    {
        return static::addRoute('DELETE', $path, $callback);
    }

    /**
     * @param $path
     * @param $callback
     * @return BaseRoute
     */
    public static function head($path, $callback)
    {
        return static::addRoute('HEAD', $path, $callback);
    }

    /**
     * @param $path
     * @param $callback
     * @return BaseRoute
     */
    public static function options($path, $callback)
    {
        return static::addRoute('OPTIONS', $path, $callback);
    }

    /**
     * @param $path
     * @param $callback
     * @return BaseRoute
     */
    public static function any($path, $callback)
    {
        return static::addRoute(['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'], $path, $callback);
    }

    /**
     * @param $method
     * @param $path
     * @param $callback
     * @return BaseRoute
     */
    public static function add($method, $path, $callback)
    {
        return static::addRoute($method, $path, $callback);
    }

    /**
     * @param $path
     * @param $callback
     * @return Route
     */
    public static function group($path, $callback)
    {
        $previous_group_prefix = static::$_groupPrefix;
        static::$_groupPrefix = $previous_group_prefix . $path;
        $instance = static::$_instance = new static;
        static::$_collector->addGroup($path, $callback);
        static::$_instance = null;
        static::$_groupPrefix = $previous_group_prefix;
        return $instance;
    }

    /**
     * @return array
     */
    public static function getRoutes()
    {
        return static::$_allRoutes;
    }

    /**
     * disableDefaultRoute.
     */
    public static function disableDefaultRoute()
    {
        static::$_disableDefaultRoute = true;
    }

    /**
     * @return bool
     */
    public static function hasDisableDefaultRoute()
    {
        return static::$_disableDefaultRoute === true;
    }

    /**
     * @param $middleware
     * @return $this
     */
    public function middleware($middleware)
    {
        foreach ($this->_routes as $route) {
            $route->middleware($middleware);
        }
    }

    /**
     * @param BaseRoute $route
     */
    public function collect(BaseRoute $route)
    {
        $this->_routes[] = $route;
    }

    /**
     * @param $name
     * @param BaseRoute $instance
     */
    public static function setByName($name, BaseRoute $instance)
    {
        static::$_nameList[$name] = $instance;
    }

    /**
     * @param $name
     * @return null|BaseRoute
     */
    public static function getByName($name)
    {
        return static::$_nameList[$name] ?? null;
    }

    /**
     * @param $method
     * @param $path
     * @return array
     */
    public static function dispatch($method, $path)
    {
        return static::$_dispatcher->dispatch($method, $path);
    }

    /**
     * @param $path
     * @param $callback
     * @return array|bool|callable
     */
    public static function convertToCallable($path, $callback)
    {
        if (is_string($callback) && strpos($callback, '@')) {
            $callback = explode('@', $callback, 2);
        }

        if (is_array($callback)) {
            $callback = array_values($callback);
            if (isset($callback[1]) && is_string($callback[0]) && class_exists($callback[0])) {
                $callback = [static::container()->get($callback[0]), $callback[1]];
            }
        }

        if (!is_callable($callback)) {
            echo "Route set to $path is not callable\n";
            return false;
        }

        return $callback;
    }

    /**
     * @param $methods
     * @param $path
     * @param $callback
     * @return BaseRoute
     */
    protected static function addRoute($methods, $path, $callback)
    {
        static::$_hasRoute = true;
        $route = new BaseRoute($methods, static::$_groupPrefix . $path, $callback);
        static::$_allRoutes[] = $route;

        if ($callback = static::convertToCallable($path, $callback)) {
            static::$_collector->addRoute($methods, $path, ['callback' => $callback, 'route' => $route]);
        }
        if (static::$_instance) {
            static::$_instance->collect($route);
        }
        return $route;
    }

    /**
     * @return bool
     */
    public static function load($route_path)
    {
        if (!is_dir($route_path)) {
            $route_path = pathinfo($route_path, PATHINFO_DIRNAME);
        }
        static::$_dispatcher = simpleDispatcher(function (RouteCollector $route) use ($route_path) {
            Route::setCollector($route);
            $route_config_file = $route_path . '/route.php';
            if (\is_file($route_config_file)) {
                require_once $route_config_file;
            }
        });
        return static::$_hasRoute;
    }

    /**
     * @param $route
     */
    public static function setCollector($route)
    {
        static::$_collector = $route;
    }

    /**
     * @param callable $callback
     */
    public static function fallback(callable $callback)
    {
        if (is_callable($callback)) {
            static::$_fallback = $callback;
        }
    }

    /**
     * @return callable|null
     */
    public static function getFallback()
    {
        return is_callable(static::$_fallback) ? static::$_fallback : null;
    }

    /**
     * @param $container
     * @return ContainerInterface
     */
    public static function container($container = null)
    {
        if ($container) {
            static::$_container = $container;
        }
        if (!static::$_container) {
            static::$_container = App::container();
        }
        return static::$_container;
    }
}
