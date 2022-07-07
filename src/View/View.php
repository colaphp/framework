<?php

namespace Cola\View;

use Cola\Contracts\View as ViewContract;
use Cola\Support\Str;

/**
 * Class View
 * @package Cola\View
 */
class View implements ViewContract
{
    /**
     * @var array
     */
    protected static $_vars = [];

    /**
     * @param $name
     * @param null $value
     */
    public static function assign($name, $value = null)
    {
        static::$_vars = array_merge(static::$_vars, is_array($name) ? $name : [$name => $value]);
    }

    /**
     * @param $template
     * @param $vars
     * @param string $app
     * @return string
     */
    public static function render($template, $vars, $app = null): string
    {
        static $views = [];

        $app = is_null($app) ? request()->app : $app;
        $app = empty($app) ? '__default' : $app;

        if (!isset($views[$app])) {
            $subDir = $app === '__default' ? '' : DIRECTORY_SEPARATOR . Str::snake($app);
            $viewPath = resource_path('views' . $subDir);
            $cachePath = runtime_path('views' . $subDir);
            if (!is_dir($cachePath)) {
                mkdir($cachePath, 0755, true);
            }
            $views[$app] = $views[$app] ?? new Blade($viewPath, $cachePath);
        }

        $vars = array_merge(static::$_vars, $vars);
        $content = $views[$app]->render($template, $vars);
        static::$_vars = [];
        return $content;
    }
}
