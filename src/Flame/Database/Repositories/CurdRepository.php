<?php

declare(strict_types=1);

namespace Flame\Database\Repositories;

use Flame\Database\Contracts\CurdRepositoryInterface;
use Flame\Database\Model;
use Illuminate\Database\Query\Builder;

/**
 * @method Builder builder() 查询构造器
 * @method Model model() 定义数据数据模型类
 */
abstract class CurdRepository implements CurdRepositoryInterface
{
    /**
     * 保存给定的实体数据
     */
    public function save(array $data): mixed
    {
        return $this->builder()->insertGetId($data);
    }

    /**
     * 保存给定的实体数据数组
     */
    public function saveAll(array $data): bool
    {
        return $this->builder()->insert($data);
    }

    /**
     * 根据实体的 主键 检索实体
     */
    public function findById(mixed $id): array
    {
        $result = $this->builder()->find($id);
        if ($result->isEmpty()) {
            return [];
        }

        return collect($result)->toArray();
    }

    /**
     * 根据条件检索实体
     */
    public function find(array $condition = [], string $order = '', string $sort = 'desc'): array
    {
        $order = empty($order) ? $this->getPrimaryKey() : $order;

        $result = $this->builder()->where($condition)->orderBy($order, $sort)->first();
        if (is_null($result)) {
            return [];
        }

        return collect($result)->toArray();
    }

    /**
     * 查询某个字段的值
     */
    public function value(string $field, array $condition = []): mixed
    {
        return $this->builder()->where($condition)->value($field);
    }

    /**
     * 获取某一列的值
     */
    public function pluck(string $field, array $condition = []): array
    {
        $result = $this->builder()->where($condition)->pluck($field);

        return collect($result)->toArray();
    }

    /**
     * 返回具有给定 主键 的实体是否存在
     */
    public function existsById(mixed $id): bool
    {
        return $this->builder()->find($id)->exists();
    }

    /**
     * 返回该类型的所有实例
     */
    public function findAll(array $condition = [], string $order = '', string $sort = 'desc'): array
    {
        $order = empty($order) ? $this->getPrimaryKey() : $order;

        $result = $this->builder()->where($condition)->orderBy($order, $sort)->get();
        if ($result->isEmpty()) {
            return [];
        }

        return $result->toArray();
    }

    /**
     * 返回具有给定 主键 类型的所有实例
     */
    public function findAllByIds(array $ids, string $order = '', string $sort = 'desc'): array
    {
        $primaryKey = $this->getPrimaryKey();

        $order = empty($order) ? $primaryKey : $order;

        $result = $this->builder()->whereIn($primaryKey, $ids)->orderBy($order, $sort)->get();
        if ($result->isEmpty()) {
            return [];
        }

        return $result->toArray();
    }

    /**
     * 返回可用实体的数量
     */
    public function count(array $condition = []): int
    {
        return $this->builder()->where($condition)->count();
    }

    /**
     * 删除具有给定 主键 的实体
     */
    public function deleteById(mixed $id): bool
    {
        $affectedRows = $this->builder()->find($id)->delete();

        return $affectedRows > 0;
    }

    /**
     * 删除给定条件的实体
     */
    public function delete(array $condition = []): bool
    {
        if (empty($condition)) {
            return false;
        }

        $affectedRows = $this->builder()->where($condition)->delete();

        return $affectedRows > 0;
    }

    /**
     * 删除具有给定 主键 类型的所有实例
     */
    public function deleteAllByIds(array $ids): bool
    {
        if (empty($ids)) {
            return false;
        }

        $primaryKey = $this->getPrimaryKey();

        $affectedRows = $this->builder()->whereIn($primaryKey, $ids)->delete();

        return $affectedRows > 0;
    }

    /**
     * 分页查询
     */
    public function page(array $condition = [], int $page = 1, int $perPage = 20, string $order = '', string $sort = 'desc'): array
    {
        $order = empty($order) ? $this->getPrimaryKey() : $order;

        $result = $this->builder()->where($condition)->orderBy($order, $sort)->paginate($perPage, ['*'], 'page', $page);
        if ($result->isEmpty()) {
            return [];
        }

        return $result->toArray();
    }

    /**
     * 按 主键 更新数据
     */
    public function updateById(array $data, mixed $id): int
    {
        $primaryKey = $this->getPrimaryKey();

        return $this->builder()->where($primaryKey, $id)->update($data);
    }

    /**
     * 按条件更新数据
     */
    public function update(array $data, array $condition = []): int
    {
        if (empty($condition)) {
            return 0;
        }

        return $this->builder()->where($condition)->update($data);
    }

    /**
     * 获取主键名
     */
    public function getPrimaryKey(): string
    {
        return $this->model()->getKey();
    }
}
