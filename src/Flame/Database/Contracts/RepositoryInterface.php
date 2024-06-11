<?php

declare(strict_types=1);

namespace Flame\Database\Contracts;

use Flame\Database\Model;
use Illuminate\Database\Query\Builder;

interface RepositoryInterface
{
    /**
     * 获取查询构造器对象
     */
    public function builder(): Builder;

    /**
     * 获取模型对象
     */
    public function model(): Model;
}
