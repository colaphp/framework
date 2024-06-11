<?php

declare(strict_types=1);

namespace Flame\Queue\Contracts;

interface Factory
{
    /**
     * 推送队列任务
     */
    public function push(JobInterface $job): string;

    /**
     * 出队列
     */
    public function pull(int $count = 10): array;

    /**
     * 删除消息
     */
    public function remove(array $ids): int;

    /**
     * 获取队列任务总数
     */
    public function count(): int;
}
