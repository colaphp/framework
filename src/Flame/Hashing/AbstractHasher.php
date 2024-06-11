<?php

declare(strict_types=1);

namespace Flame\Hashing;

abstract class AbstractHasher
{
    /**
     * 获取给定散列值的信息
     */
    public function info(string $hashedValue): array
    {
        return password_get_info($hashedValue);
    }

    /**
     * 根据散列检查给定的值
     */
    public function check(string $value, ?string $hashedValue, array $options = []): bool
    {
        if (is_null($hashedValue) || strlen($hashedValue) === 0) {
            return false;
        }

        return password_verify($value, $hashedValue);
    }
}
