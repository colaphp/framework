<?php

declare(strict_types=1);

namespace Flame\Config;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class Config
{
    protected static array $config = [];

    protected static string $configPath = '';

    protected static bool $loaded = false;

    public static function load(string $configPath, array $excludeFile = [], ?string $key = null): void
    {
        static::$configPath = $configPath;
        if (! $configPath) {
            return;
        }
        static::$loaded = false;
        $config = static::loadFromDir($configPath, $excludeFile);
        if (! $config) {
            static::$loaded = true;

            return;
        }
        if ($key !== null) {
            foreach (array_reverse(explode('.', $key)) as $k) {
                $config = [$k => $config];
            }
        }
        static::$config = array_replace_recursive(static::$config, $config);
        static::$loaded = true;
    }

    public static function clear(): void
    {
        static::$config = [];
    }

    public static function loadFromDir(string $configPath, array $excludeFile = []): array
    {
        $allConfig = [];
        $dirIterator = new RecursiveDirectoryIterator($configPath, FilesystemIterator::FOLLOW_SYMLINKS);
        $iterator = new RecursiveIteratorIterator($dirIterator);
        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if (is_dir($file->getPathname()) || $file->getExtension() != 'php' || in_array($file->getBaseName('.php'), $excludeFile)) {
                continue;
            }
            $appConfigFile = $file->getPath().'/app.php';
            if (! is_file($appConfigFile)) {
                continue;
            }
            $relativePath = str_replace($configPath.DIRECTORY_SEPARATOR, '', substr($file->getPathname(), 0, -4));
            $explode = array_reverse(explode(DIRECTORY_SEPARATOR, $relativePath));
            if (count($explode) >= 2) {
                $appConfig = include $appConfigFile;
                if (empty($appConfig['enable'])) {
                    continue;
                }
            }
            $config = include $file;
            foreach ($explode as $section) {
                $tmp = [];
                $tmp[$section] = $config;
                $config = $tmp;
            }
            $allConfig = array_replace_recursive($allConfig, $config);
        }

        return $allConfig;
    }

    public static function get(?string $key = null, $default = null)
    {
        if ($key === null) {
            return static::$config;
        }
        $keyArray = explode('.', $key);
        $value = static::$config;
        $found = true;
        foreach ($keyArray as $index) {
            if (! isset($value[$index])) {
                if (static::$loaded) {
                    return $default;
                }
                $found = false;
                break;
            }
            $value = $value[$index];
        }
        if ($found) {
            return $value;
        }

        return static::read($key, $default);
    }

    protected static function read(string $key, $default = null)
    {
        $path = static::$configPath;
        if ($path === '') {
            return $default;
        }
        $keys = $keyArray = explode('.', $key);
        foreach ($keyArray as $index => $section) {
            unset($keys[$index]);
            if (is_file($file = "$path/$section.php")) {
                $config = include $file;

                return static::find($keys, $config, $default);
            }
            if (! is_dir($path = "$path/$section")) {
                return $default;
            }
        }

        return $default;
    }

    protected static function find(array $keyArray, $stack, $default)
    {
        if (! is_array($stack)) {
            return $default;
        }
        $value = $stack;
        foreach ($keyArray as $index) {
            if (! isset($value[$index])) {
                return $default;
            }
            $value = $value[$index];
        }

        return $value;
    }
}
