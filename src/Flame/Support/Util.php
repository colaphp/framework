<?php

declare(strict_types=1);

namespace Flame\Support;

class Util
{
    public static function scanDir(string $basePath, bool $withBasePath = true): array
    {
        if (! is_dir($basePath)) {
            return [];
        }
        $paths = array_diff(scandir($basePath), ['.', '..']) ?: [];

        return $withBasePath ? array_map(static function ($path) use ($basePath) {
            return $basePath.DIRECTORY_SEPARATOR.$path;
        }, $paths) : $paths;
    }
}
