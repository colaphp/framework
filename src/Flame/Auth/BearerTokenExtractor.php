<?php

declare(strict_types=1);

namespace Flame\Auth;

use Flame\Auth\Exception\ExtractTokenException;
use Flame\Support\Facade\Request;

class BearerTokenExtractor implements TokenExtractorInterface
{
    /**
     * 提取token
     *
     * @throws ExtractTokenException
     */
    public function extractToken(): string
    {
        return Request::bearerToken();
    }
}
