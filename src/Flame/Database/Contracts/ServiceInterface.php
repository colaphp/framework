<?php

declare(strict_types=1);

namespace Flame\Database\Contracts;

interface ServiceInterface
{
    public function getRepository(): CurdRepositoryInterface;
}
