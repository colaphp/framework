<?php

declare(strict_types=1);

namespace Flame\Auth;

class Authorization
{
    /**
     * jwt
     */
    public JWT $jwt;

    /**
     * Authorization constructor.
     */
    public function __construct(JWT $jwt)
    {
        $this->jwt = $jwt;
    }

    /**
     * 获取有效荷载
     */
    public function getPayload(TokenExtractorInterface $tokenExtractor): array
    {
        $token = $tokenExtractor->extractToken();

        return $this->jwt->parse($token);
    }

    /**
     * 根据token获取有效荷载
     */
    public function getPayloadByToken(string $token): array
    {
        return $this->jwt->parse($token);
    }

    /**
     * 创建token
     */
    public function createToken(array $payload): string
    {
        return $this->jwt->create($payload);
    }
}
