<?php

namespace Cola\Foundation\Exception;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class NotFoundException
 * @package Cola\Foundation\Exception
 */
class NotFoundException extends Exception implements NotFoundExceptionInterface
{
}
