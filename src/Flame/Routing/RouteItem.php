<?php

declare(strict_types=1);

namespace Flame\Routing;

class RouteItem
{
    protected ?string $name = null;

    protected array $methods = [];

    protected string $path = '';

    protected $callback = null;

    protected array $middlewares = [];

    protected array $params = [];

    /**
     * Route constructor.
     */
    public function __construct(array $methods, string $path, $callback)
    {
        $this->methods = $methods;
        $this->path = $path;
        $this->callback = $callback;
    }

    /**
     * Get name.
     */
    public function getName(): ?string
    {
        return $this->name ?? null;
    }

    /**
     * Name.
     */
    public function name(string $name): RouteItem
    {
        $this->name = $name;
        Route::setByName($name, $this);

        return $this;
    }

    /**
     * Middleware.
     */
    public function middleware($middleware = null): array|static
    {
        if ($middleware === null) {
            return $this->middlewares;
        }
        $this->middlewares = array_merge($this->middlewares, is_array($middleware) ? array_reverse($middleware) : [$middleware]);

        return $this;
    }

    /**
     * GetPath.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * GetMethods.
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * GetCallback.
     */
    public function getCallback(): ?callable
    {
        return $this->callback;
    }

    /**
     * GetMiddleware.
     */
    public function getMiddleware(): array
    {
        return $this->middlewares;
    }

    /**
     * Param.
     */
    public function param(?string $name = null, $default = null)
    {
        if ($name === null) {
            return $this->params;
        }

        return $this->params[$name] ?? $default;
    }

    /**
     * SetParams.
     */
    public function setParams(array $params): RouteItem
    {
        $this->params = array_merge($this->params, $params);

        return $this;
    }

    /**
     * Url.
     */
    public function url(array $parameters = []): string
    {
        if (empty($parameters)) {
            return $this->path;
        }
        $path = str_replace(['[', ']'], '', $this->path);
        $path = preg_replace_callback('/\{(.*?)(?:\:[^\}]*?)*?\}/', function ($matches) use (&$parameters) {
            if (! $parameters) {
                return $matches[0];
            }
            if (isset($parameters[$matches[1]])) {
                $value = $parameters[$matches[1]];
                unset($parameters[$matches[1]]);

                return $value;
            }
            $key = key($parameters);
            if (is_int($key)) {
                $value = $parameters[$key];
                unset($parameters[$key]);

                return $value;
            }

            return $matches[0];
        }, $path);

        return count($parameters) > 0 ? $path.'?'.http_build_query($parameters) : $path;
    }
}
