<?php

declare(strict_types=1);

namespace Flame\View;

use Flame\Support\Str;
use Flame\Contracts\View;
use Jenssegers\Blade\Blade;

class ViewManager implements View
{
    protected static array $_vars = [];

    public static function assign($name, $value = null): void
    {
        static::$_vars = array_merge(static::$_vars, is_array($name) ? $name : [$name => $value]);
    }

    public static function render(string $template, array $vars, string $app = null): string
    {
        static $views = [];

        $app = is_null($app) ? request()->app : $app;
        $app = empty($app) ? '__default' : $app;

        if (! isset($views[$app])) {
            $subDir = $app === '__default' ? '' : DIRECTORY_SEPARATOR . Str::snake($app);
            $viewPath = resource_path('views' . $subDir);
            $cachePath = runtime_path('views' . $subDir);
            if (!is_dir($cachePath)) {
                mkdir($cachePath, 0755, true);
            }
            $views[$app] = new Blade($viewPath, $cachePath);
        }

        $vars = array_merge(static::$_vars, $vars);
        $content = $views[$app]->render($template, $vars);
        static::$_vars = [];

        return $content;
    }
}
