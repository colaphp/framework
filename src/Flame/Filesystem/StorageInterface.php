<?php

declare(strict_types=1);

namespace Flame\Filesystem;

/**
 * 文件存储驱动接口
 */
interface StorageInterface
{
    /**
     * 上传文件
     */
    public function upload($object, $filePath);

    /**
     * 读取文件
     */
    public function read(string $object);

    /**
     * 写入文件
     */
    public function write(string $object, string $content, array $option = []);

    /**
     * 追加内容
     */
    public function append(string $object, string $content);

    /**
     * 删除文件
     */
    public function delete($object): bool;

    /**
     * 判断文件存在
     */
    public function isExists($object): bool;

    /**
     * 移动文件
     */
    public function move($oldObject, $newObject): bool;

    /**
     * 生成url链接
     */
    public function url(string $url, int $timeout = 3600, array $options = []): string;

    /**
     * 生成url原始链接
     */
    public function originUrl(string $signUrl): string;
}
