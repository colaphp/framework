<?php

declare(strict_types=1);

namespace Flame\Console\Commands;

use Flame\Console\Worker\ScheduleWorker;
use Flame\Pusher\Worker\PusherWorker;
use Flame\Support\Arr;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Worker;

class WorkerCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('worker:serve')
            ->addArgument('action')
            ->addOption('mode', '-d')
            ->addOption('grace', '-g')
            ->setDescription('Start all Worker servers.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $argv;

        // 控制台参数过滤
        $argv = Arr::where($argv, function ($value, $key) {
            return $key > 0;
        });

        // 重新索引数组
        $argv = array_values($argv);

        $services = [
            PusherWorker::class,
            ScheduleWorker::class,
        ];

        $workers = config('worker');
        if (! empty($workers)) {
            $services = array_merge($services, $workers);
        }

        // 实例化每一个服务
        foreach ($services as $service) {
            new $service;
        }

        // 运行所有服务
        Worker::runAll();

        return 0;
    }
}
