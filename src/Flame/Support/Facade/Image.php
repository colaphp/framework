<?php

declare(strict_types=1);

namespace Flame\Support\Facade;

use Intervention\Image\ImageManager;

/**
 * @mixin ImageManager
 */
class Image extends Facade
{
    protected static function getFacadeClass(): string
    {
        self::$instance[ImageManager::class] = ImageManager::gd();

        return ImageManager::class;
    }
}
