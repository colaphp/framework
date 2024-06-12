<?php

declare(strict_types=1);

namespace Flame\Filesystem;

use RuntimeException;

class FileDriver implements StorageInterface
{
    private array $config = [
        'root' => '',
        'asset_url' => '', // 公网可访问的自定义域名
    ];

    public function __construct(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }

    public function upload($object, $filePath): bool
    {
        return rename($filePath, $this->absolutePath($object));
    }

    public function read($object): string
    {
        return file_get_contents($this->absolutePath($object));
    }

    public function write($object, $content, $option = null): bool
    {
        return file_put_contents($this->absolutePath($object), $content, LOCK_EX);
    }

    public function append($object, $content)
    {
        return file_put_contents($this->absolutePath($object), $content, LOCK_EX | FILE_APPEND);
    }

    public function delete($object): bool
    {
        return @unlink($this->absolutePath($object));
    }

    public function isExists($object): bool
    {
        return file_exists($this->absolutePath($object));
    }

    public function move($oldObject, $newObject): bool
    {
        return rename($this->absolutePath($oldObject), $this->absolutePath($newObject));
    }

    public function url(string $url, int $timeout = 3600, array $options = []): string
    {
        return rtrim($this->config['asset_url'], '/').'/'.ltrim($url, '/');
    }

    public function originUrl(string $signUrl): string
    {
        $parseUrls = parse_url($signUrl);

        return $parseUrls['path'] ? ltrim($parseUrls['path'], '/') : '';
    }

    private function absolutePath(string $name): string
    {
        $path = rtrim($this->config['root'], '/').'/'.ltrim($name, '/');

        $this->checkFolder($path);

        return $path;
    }

    private function checkFolder(string $path): void
    {
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (! is_dir($dir)) {
            throw new RuntimeException("Can't create folder: {$dir}");
        }
    }
}
