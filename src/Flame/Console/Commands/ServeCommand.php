<?php

declare(strict_types=1);

namespace Flame\Console\Commands;

use Flame\Config\Config;
use Flame\Foundation\App;
use Flame\Support\Arr;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

class ServeCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('serve')
            ->addArgument('action', InputArgument::OPTIONAL, 'php artisan serve {start|reload|stop}', 'start')
            ->addOption('daemon', '-d', null, 'Start in DAEMON mode')
            ->addOption('grace', '-g')
            ->setDescription('Serve the application.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $argv;

        // 控制台参数过滤，重新索引数组
        $argv = Arr::where($argv, function ($value, $key) {
            return $key > 0;
        });
        $argv = array_values($argv);

        ini_set('display_errors', 'on');
        error_reporting(E_ALL);

        Config::load(config_path());
        $errorReporting = config('app.error_reporting');
        if (isset($errorReporting)) {
            error_reporting($errorReporting);
        }

        /*$whoops = new Run;
        $whoops->pushHandler(new PrettyPageHandler);
        $whoops->register();*/

        $runtimeLogsPath = runtime_path().DIRECTORY_SEPARATOR.'logs';
        if (! file_exists($runtimeLogsPath) || ! is_dir($runtimeLogsPath)) {
            if (! mkdir($runtimeLogsPath, 0777, true)) {
                throw new RuntimeException('Failed to create runtime logs directory. Please check the permission.');
            }
        }

        $runtimeViewsPath = runtime_path().DIRECTORY_SEPARATOR.'views';
        if (! file_exists($runtimeViewsPath) || ! is_dir($runtimeViewsPath)) {
            if (! mkdir($runtimeViewsPath, 0777, true)) {
                throw new RuntimeException('Failed to create runtime views directory. Please check the permission.');
            }
        }

        Worker::$onMasterReload = function () {
            if (function_exists('opcache_get_status')) {
                if ($status = opcache_get_status()) {
                    if (isset($status['scripts']) && $scripts = $status['scripts']) {
                        foreach (array_keys($scripts) as $file) {
                            opcache_invalidate($file, true);
                        }
                    }
                }
            }
        };

        $config = config('server');
        Worker::$pidFile = $config['pid_file'];
        Worker::$stdoutFile = $config['stdout_file'];
        Worker::$logFile = $config['log_file'];
        Worker::$eventLoopClass = $config['event_loop'] ?? '';
        TcpConnection::$defaultMaxPackageSize = $config['max_package_size'] ?? 10 * 1024 * 1024;
        if (property_exists(Worker::class, 'statusFile')) {
            Worker::$statusFile = $config['status_file'] ?? '';
        }
        if (property_exists(Worker::class, 'stopTimeout')) {
            Worker::$stopTimeout = $config['stop_timeout'] ?? 2;
        }

        if ($config['listen']) {
            $worker = new Worker($config['listen'], $config['context']);
            $propertyMap = [
                'name',
                'count',
                'user',
                'group',
                'reusePort',
                'transport',
                'protocol',
            ];
            foreach ($propertyMap as $property) {
                if (isset($config[$property])) {
                    $worker->$property = $config[$property];
                }
            }

            $worker->onWorkerStart = function ($worker) {
                require_once dirname(__DIR__, 2).'/Support/bootstrap.php';
                $app = new App(app_path(), public_path());
                $worker->onMessage = [$app, 'onMessage'];
                call_user_func([$app, 'onWorkerStart'], $worker);
            };
        }

        // Windows does not support custom processes.
        if (DIRECTORY_SEPARATOR === '/') {
            foreach (config('process', []) as $processName => $config) {
                worker_start($processName, $config);
            }
        }

        Worker::runAll();

        return 0;
    }
}
