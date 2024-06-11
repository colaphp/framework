<?php

declare(strict_types=1);

namespace Flame\Hashing\Contract;

interface Hasher
{
    /**
     * 获取给定散列值的信息
     */
    public function info(string $hashedValue): array;

    /**
     * 对给定值进行散列
     */
    public function make(string $value, array $options = []): string;

    /**
     * 根据散列检查给定的值
     */
    public function check(string $value, string $hashedValue, array $options = []): bool;

    /**
     * 检查给定的散列是否已经使用给定的选项进行了散列
     */
    public function needsRehash(string $hashedValue, array $options = []): bool;
}
