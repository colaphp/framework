<?php

declare(strict_types=1);

namespace Flame\Support\Facade;

use Flame\Encryption\Encrypter;

/**
 * @mixin Encrypter
 */
class Crypt extends Facade
{
    protected static function getFacadeClass(): string
    {
        return Encrypter::class;
    }
}
