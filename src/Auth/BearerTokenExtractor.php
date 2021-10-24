<?php

namespace Swift\Auth;

use Swift\Auth\Exception\ExtractTokenException;
use Psr\Http\Message\MessageInterface;

/**
 * Class BearerTokenExtractor
 * @package Swift\Auth
 */
class BearerTokenExtractor implements TokenExtractorInterface
{
    /**
     * @var MessageInterface
     */
    public MessageInterface $request;

    /**
     * BearerTokenExtractor constructor.
     * @param MessageInterface $request
     */
    public function __construct(MessageInterface $request)
    {
        $this->request = $request;
    }

    /**
     * 提取token
     * @return string
     */
    public function extractToken(): string
    {
        $authorization = $this->request->getHeaderLine('authorization');
        if (!str_starts_with($authorization, 'Bearer ')) {
            throw new ExtractTokenException('Failed to extract token.');
        }
        return (string)substr($authorization, 7);
    }
}
