<?php

declare(strict_types=1);

namespace Flame\Console;

use Flame\Config\Config;
use Flame\Support\Str;
use Phinx\Console\Command;
use Symfony\Component\Console\Application;

class Kernel extends Application
{
    /**
     * Initialize the console application.
     */
    public function __construct()
    {
        parent::__construct('ColaPHP Console.', '1.0');

        Config::load(config_path());

        $runtime_logs_path = runtime_path('logs');
        if (! file_exists($runtime_logs_path) || ! is_dir($runtime_logs_path)) {
            if (! mkdir($runtime_logs_path, 0777, true)) {
                throw new \RuntimeException('Failed to create runtime logs directory. Please check the permission.');
            }
        }

        $runtime_sessions_path = runtime_path('sessions');
        if (! file_exists($runtime_sessions_path) || ! is_dir($runtime_sessions_path)) {
            if (! mkdir($runtime_sessions_path, 0777, true)) {
                throw new \RuntimeException('Failed to create runtime sessions directory. Please check the permission.');
            }
        }

        $runtime_views_path = runtime_path('views');
        if (! file_exists($runtime_views_path) || ! is_dir($runtime_views_path)) {
            if (! mkdir($runtime_views_path, 0777, true)) {
                throw new \RuntimeException('Failed to create runtime views directory. Please check the permission.');
            }
        }

        $this->addCommands([
            new Command\Create(),
            new Command\Migrate(),
            new Command\Rollback(),
            new Command\Status(),
            new Command\SeedCreate(),
            new Command\SeedRun(),
        ]);

        // Load commands
        $commands = glob(dirname(__DIR__).'/*/Commands/*Command.php');
        $this->registerCommands($commands);

        foreach (config('app.providers', []) as $class_name) {
            /** @var \Flame\Contracts\Bootstrap $class_name */
            $class_name::start(null);
        }
    }

    public function registerCommands(array $files): void
    {
        $pattern1 = '/\/(\w+\/\w+\/\w+\/\w+Command)\.php/';
        $pattern2 = '/\/(\w+\/\w+\/\w+\/\w+\/\w+Command)\.php/';
        foreach ($files as $file) {
            if (str_contains($file, 'Bundles')) {
                $this->registerCommand($pattern2, $file);
            } else {
                $this->registerCommand($pattern1, $file);
            }
        }
    }

    private function registerCommand(string $pattern, string $file): void
    {
        preg_match($pattern, str_replace('\\', '/', $file), $matches);
        if (isset($matches[1])) {
            $command = str_replace('/', '\\', Str::ucfirst($matches[1]));
            $this->add(new $command());
        }
    }
}
