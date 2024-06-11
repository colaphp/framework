<?php

declare(strict_types=1);

namespace Flame\Console\Commands;

use Exception;
use Flame\Support\Facade\Log;
use Flame\Support\Facade\Queue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @see https://learnku.com/docs/laravel/10.x/queuesmd/14873#dd18c3
 */
class QueueWorkCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('queue:work')
            ->addArgument('queue', InputArgument::REQUIRED, 'Queue name')
            ->setDescription('The queue worker.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queueName = $input->getArgument('queue');

        $jobs = Queue::instance($queueName)->pull();
        if (empty($jobs)) {
            sleep(10);
        }

        foreach ($jobs as $id => $job) {
            try {
                $job->handle();
                // 完成后在队列中删除
                Queue::instance($queueName)->remove([$id]);
            } catch (Exception $e) {
                Log::error($e);
            }
        }

        return 1;
    }
}
