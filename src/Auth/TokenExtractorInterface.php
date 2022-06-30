<?php

namespace Cola\Auth;

/**
 * Interface TokenExtractorInterface
 * @package Cola\Auth
 */
interface TokenExtractorInterface
{
    /**
     * 提取token
     * @return string
     */
    public function extractToken(): string;
}
