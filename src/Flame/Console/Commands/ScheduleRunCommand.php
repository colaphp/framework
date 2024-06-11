<?php

declare(strict_types=1);

namespace Flame\Console\Commands;

use Flame\Console\Contracts\ScheduleTaskInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScheduleRunCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('schedule:run')
            ->addArgument('scheduler')
            ->setDescription('Run task scheduler.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $argv;

        if (! isset($argv[2])) {
            $output->writeln('缺少任务程序参数，示例：php artisan schedule:run App/Scheduler/SynergyTask');

            return 0;
        }

        $scheduler = str_replace('/', '\\', rtrim($argv[2], '.php'));
        if (! class_exists($scheduler)) {
            $output->writeln('没有找到任务程序：'.$scheduler.'::class');

            return 0;
        }

        /** @var ScheduleTaskInterface $task */
        $task = new $scheduler();
        $task->handle();

        return 0;
    }
}
