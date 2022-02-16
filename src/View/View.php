<?php

namespace Swift\View;

use Swift\Contracts\View as ViewContract;

/**
 * Class View
 * @package Swift\View
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

        if (!isset($views[$app])) {
            $viewPath = resource_path('views' . ($app ? DIRECTORY_SEPARATOR . $app : ''));
            $cachePath = runtime_path('views' . ($app ? DIRECTORY_SEPARATOR . $app : ''));
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
