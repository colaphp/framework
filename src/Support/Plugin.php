<?php

namespace Swift\Support;

class Plugin
{
    public static function install($event)
    {
        if (!static::requireAutoloadFile()) {
            return;
        }
        $autoload = $event->getOperation()->getPackage()->getAutoload();
        if (!isset($autoload['psr-4'])) {
            return;
        }
        $namespace = key($autoload['psr-4']);
        $install_function = "\\{$namespace}Install::install";
        $plugin_const = "\\{$namespace}Install::DAOMAN_PLUGIN";
        if (defined($plugin_const) && is_callable($install_function)) {
            $install_function();
        }
    }

    public static function uninstall($event)
    {
        if (!static::requireAutoloadFile()) {
            return;
        }
        $autoload = $event->getOperation()->getPackage()->getAutoload();
        if (!isset($autoload['psr-4'])) {
            return;
        }
        $namespace = key($autoload['psr-4']);
        $uninstall_function = "\\{$namespace}Install::uninstall";
        $plugin_const = "\\{$namespace}Install::DAOMAN_PLUGIN";
        if (defined($plugin_const) && is_callable($uninstall_function)) {
            $uninstall_function();
        }
    }

    protected static function requireAutoloadFile()
    {
        if (!is_file($autoload_file = base_path('vendor/autoload.php'))) {
            return false;
        }
        require_once base_path('vendor/autoload.php');
        return true;
    }
}