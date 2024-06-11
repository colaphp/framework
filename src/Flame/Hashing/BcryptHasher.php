<?php

declare(strict_types=1);

namespace Flame\Hashing;

use Flame\Hashing\Contract\Hasher as HasherContract;
use RuntimeException;

class BcryptHasher extends AbstractHasher implements HasherContract
{
    /**
     * 默认的成本因子
     */
    protected int $rounds = 10;

    /**
     * 是否进行算法检查
     */
    protected bool $verifyAlgorithm = false;

    /**
     * 创建一个新的散列实例
     */
    public function __construct(array $options = [])
    {
        $this->rounds = $options['rounds'] ?? $this->rounds;
        $this->verifyAlgorithm = $options['verify'] ?? $this->verifyAlgorithm;
    }

    /**
     * 对给定值进行散列
     *
     * @throws RuntimeException
     */
    public function make(string $value, array $options = []): string
    {
        $hash = password_hash($value, PASSWORD_BCRYPT, [
            'cost' => $this->cost($options),
        ]);

        if ($hash === false) {
            throw new RuntimeException('Bcrypt hashing not supported.');
        }

        return $hash;
    }

    /**
     * 根据散列检查给定的值
     *
     * @throws RuntimeException
     */
    public function check(string $value, $hashedValue, array $options = []): bool
    {
        if ($this->verifyAlgorithm && $this->info($hashedValue)['algoName'] !== 'bcrypt') {
            throw new RuntimeException('This password does not use the Bcrypt algorithm.');
        }

        return parent::check($value, $hashedValue, $options);
    }

    /**
     * 检查给定的散列是否已经使用给定的选项进行了散列
     */
    public function needsRehash(string $hashedValue, array $options = []): bool
    {
        return password_needs_rehash($hashedValue, PASSWORD_BCRYPT, [
            'cost' => $this->cost($options),
        ]);
    }

    /**
     * 设置默认密码工作系数.
     */
    public function setRounds(int $rounds): static
    {
        $this->rounds = (int) $rounds;

        return $this;
    }

    /**
     * 从选项数组中提取成本值
     */
    protected function cost(array $options = []): int
    {
        return $options['rounds'] ?? $this->rounds;
    }
}
