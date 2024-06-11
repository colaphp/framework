<?php

declare(strict_types=1);

namespace Flame\Encryption;

use RuntimeException;

class MissingAppKeyException extends RuntimeException
{
    public function __construct($message = 'No application encryption key has been specified.')
    {
        parent::__construct($message);
    }
}
