<?php

declare(strict_types=1);

namespace Flame\Hashing;

use Flame\Hashing\Contract\Hasher;
use Flame\Support\Manager;

class HashManager extends Manager implements Hasher
{
    private array $config;

    public function __construct()
    {
        $this->config = config('hashing');
    }

    /**
     * 创建Bcrypt哈希驱动程序的实例
     */
    public function createBcryptDriver(): BcryptHasher
    {
        return new BcryptHasher(config('bcrypt') ?? []);
    }

    /**
     * 获取有关给定散列值的信息
     */
    public function info(string $hashedValue): array
    {
        return $this->driver()->info($hashedValue);
    }

    /**
     * 对给定值进行散列
     */
    public function make(string $value, array $options = []): string
    {
        return $this->driver()->make($value, $options);
    }

    /**
     * 根据散列检查给定的值
     */
    public function check(string $value, string $hashedValue, array $options = []): bool
    {
        return $this->driver()->check($value, $hashedValue, $options);
    }

    /**
     * 检查给定的散列是否已经使用给定的选项进行了散列
     */
    public function needsRehash(string $hashedValue, array $options = []): bool
    {
        return $this->driver()->needsRehash($hashedValue, $options);
    }

    /**
     * 确定给定字符串是否已经散列
     */
    public function isHashed(string $value): bool
    {
        return password_get_info($value)['algo'] !== null;
    }

    /**
     * 获取默认驱动程序名称
     */
    public function getDefaultDriver(): string
    {
        return $this->config['driver'] ?? 'bcrypt';
    }
}
