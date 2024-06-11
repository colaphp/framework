<?php

declare(strict_types=1);

namespace Flame\Auth;

use Firebase\JWT\Key;

class JWT
{
    /**
     * 签名算法常量
     */
    const ALGORITHM_HS256 = 'HS256';

    const ALGORITHM_HS384 = 'HS384';

    const ALGORITHM_HS512 = 'HS512';

    const ALGORITHM_RS256 = 'RS256';

    const ALGORITHM_RS384 = 'RS384';

    const ALGORITHM_RS512 = 'RS512';

    /**
     * 钥匙
     */
    public string $key = '';

    /**
     * 私钥
     */
    public string $privateKey = '';

    /**
     * 公钥
     */
    public string $publicKey = '';

    /**
     * 签名算法
     */
    public string $algorithm = self::ALGORITHM_HS256;

    public function __construct(array $config = [])
    {
        if (isset($config['algorithm'])) {
            $this->algorithm = $config['algorithm'];
        }
        if (isset($config['key'])) {
            $this->key = $config['key'];
        }
        if (in_array($this->algorithm, [self::ALGORITHM_RS256, self::ALGORITHM_RS384, self::ALGORITHM_RS512])) {
            if (! empty($config['privateKey'])) {
                $this->privateKey = $this->getPrivateKey($config['privateKey']);
            }
            if (! empty($config['publicKey'])) {
                $this->publicKey = $this->getPublicKey($config['publicKey']);
            }
        }
    }

    /**
     * 解析Token
     */
    public function parse(string $token): array
    {
        return match ($this->algorithm) {
            self::ALGORITHM_HS256, self::ALGORITHM_HS384, self::ALGORITHM_HS512 => (array) \Firebase\JWT\JWT::decode($token, new Key($this->key, $this->algorithm)),
            self::ALGORITHM_RS256, self::ALGORITHM_RS384, self::ALGORITHM_RS512 => (array) \Firebase\JWT\JWT::decode($token, new Key($this->publicKey, $this->algorithm)),
            default => throw new \InvalidArgumentException('Invalid signature algorithm.'),
        };
    }

    /**
     * 创建Token
     */
    public function create(array $payload): string
    {
        return match ($this->algorithm) {
            self::ALGORITHM_HS256, self::ALGORITHM_HS384, self::ALGORITHM_HS512 => \Firebase\JWT\JWT::encode($payload, $this->key, $this->algorithm),
            self::ALGORITHM_RS256, self::ALGORITHM_RS384, self::ALGORITHM_RS512 => \Firebase\JWT\JWT::encode($payload, $this->privateKey, $this->algorithm),
            default => throw new \InvalidArgumentException('Invalid signature algorithm.'),
        };
    }

    private function getPrivateKey(string $privateKey): string
    {
        $privateKeyContent = file_get_contents($privateKey);

        if (stripos($privateKeyContent, 'BEGIN RSA PRIVATE KEY') === false) {
            $privateKeyContent = "-----BEGIN RSA PRIVATE KEY-----\n".$privateKeyContent."\n-----END RSA PRIVATE KEY-----";
        }

        return $privateKeyContent;
    }

    private function getPublicKey(string $publicKey): string
    {
        $publicKeyContent = file_get_contents($publicKey);

        if (stripos($publicKeyContent, 'BEGIN PUBLIC KEY') === false) {
            $publicKeyContent = "-----BEGIN PUBLIC KEY-----\n".$publicKeyContent."\n-----END PUBLIC KEY-----";
        }

        return $publicKeyContent;
    }
}
