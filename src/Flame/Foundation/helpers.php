<?php

declare(strict_types=1);

use Flame\Http\Response;
use Flame\Support\Carbon;

/**
 * 标准时间
 */
function now(): Carbon
{
    return Carbon::now();
}

/**
 * 获取操作系统
 */
function uname(): string
{
    if (strtoupper(mb_substr(PHP_OS, 0, 3)) === 'WIN') {
        return 'WIN';
    }

    return strtoupper(PHP_OS);
}

/**
 * 数据库目录
 */
function database_path(string $path = ''): string
{
    return path_combine(BASE_PATH.DIRECTORY_SEPARATOR.'database', $path);
}

/**
 * 资源目录
 */
function resource_path(string $path = ''): string
{
    return path_combine(BASE_PATH.DIRECTORY_SEPARATOR.'resources', $path);
}

/**
 * 验证邮箱地址格式
 */
function is_email(string $email): bool
{
    return ! (filter_var($email, FILTER_VALIDATE_EMAIL) === false);
}

/**
 * 验证手机号码格式
 */
function is_mobile(string $mobile): bool
{
    $rule = '/^1[3-9]\d{9}$/';

    return preg_match($rule, $mobile) === 1;
}

/**
 * URL生成
 */
function url(string $url = '', array $params = []): string
{
    $url = '/'.ltrim($url, '/');

    if (! empty($params)) {
        if (str_contains($url, '?')) {
            $url .= '&';
        } else {
            $url .= '?';
        }

        $url .= http_build_query($params, '', '&');
    }

    return $url;
}

/**
 * 缓存管理
 */
function cache(?string $name = null, $value = '', $options = null)
{
    // TODO
}

/**
 * Cookie管理
 */
function cookie(string $name, string $value = '', $option = null): Response
{
    $maxAge = $option['maxAge'] ?? null;
    $path = $option['path'] ?? '';
    $domain = $option['domain'] ?? '';
    $secure = $option['secure'] ?? false;
    $httpOnly = $option['httpOnly'] ?? false;
    $sameSite = $option['sameSite'] ?? '';

    return response()->cookie($name, $value, $maxAge, $path, $domain, $secure, $httpOnly, $sameSite);
}

/**
 * 获取下载对象
 */
function download(string $filename, string $name = ''): Response
{
    return response()->download($filename, $name);
}

/**
 * 以 MIME 值获取文件编码
 */
function file_encoding(string $file): string
{
    $file_info = finfo_open(FILEINFO_MIME_ENCODING);
    $file_encoding = finfo_file($file_info, $file);
    finfo_close($file_info);

    return strtoupper($file_encoding);
}

/**
 * 将文件大小数字转换为人类可读的等价物
 */
function file_size(int|float $bytes, int $precision = 0, int $mode = PHP_ROUND_HALF_UP): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

    for ($i = 0; ($bytes / 1024) > 0.9 && ($i < count($units) - 1); $i++) {
        $bytes /= 1024;
    }

    return sprintf('%s %s', round($bytes, $precision, $mode), $units[$i]);
}
