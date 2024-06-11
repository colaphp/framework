<?php

declare(strict_types=1);

namespace Flame\View;

use Exception;
use Flame\View\Contracts\View as ViewContract;
use Illuminate\Contracts\View\Factory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ViewManager implements ViewContract
{
    private static ?Factory $view = null;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct()
    {
        if (is_null(self::$view)) {
            $viewPath = resource_path('views');
            $cachePath = runtime_path('views');
            if (! is_dir($cachePath)) {
                mkdir($cachePath, 0755, true);
            }

            self::$view = new Blade($viewPath, $cachePath);
        }
    }

    protected array $vars = [];

    public function assign($name, $value = null): void
    {
        $this->vars = array_merge($this->vars, is_array($name) ? $name : [$name => $value]);
    }

    public function render($template, $vars): string
    {
        $vars = array_merge($this->vars, $vars);

        return self::$view->render($template, $vars);
    }

    /**
     * @throws Exception
     */
    public function contentRender(string $content, array $vars): string
    {
        throw new Exception('暂时还不支持内容解析');
    }

    public function exists($template): bool
    {
        return self::$view->exists($template);
    }
}
