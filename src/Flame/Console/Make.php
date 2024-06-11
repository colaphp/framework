<?php

declare(strict_types=1);

namespace Flame\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Make extends Command
{
    protected string $type;

    abstract protected function getStub();

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the class');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = trim($input->getArgument('name'));

        $classname = $this->getClassName($name);

        $pathname = $this->getPathName($classname);

        if (is_file($pathname)) {
            $output->writeln('<error>'.$this->type.':'.$classname.' already exists!</error>');

            return false;
        }

        if (! is_dir(dirname($pathname))) {
            mkdir(dirname($pathname), 0755, true);
        }

        file_put_contents($pathname, $this->buildClass($classname));

        $output->writeln('<info>'.$this->type.':'.$classname.' created successfully.</info>');
    }

    protected function buildClass(string $name): string
    {
        $stub = file_get_contents($this->getStub());

        $namespace = trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');

        $class = str_replace($namespace.'\\', '', $name);

        return str_replace(['{%className%}', '{%namespace%}', '{%app_namespace%}'], [
            $class,
            $namespace,
            $this->getNamespace(),
        ], $stub);
    }

    protected function getPathName(string $name): string
    {
        $name = substr($name, 4);

        return app_path().ltrim(str_replace('\\', '/', $name), '/').'.php';
    }

    protected function getClassName(string $name): string
    {
        if (str_contains($name, '\\')) {
            return $name;
        }

        if (str_contains($name, '/')) {
            $name = str_replace('/', '\\', $name);
        }

        return $this->getNamespace().'\\'.$name;
    }

    protected function getNamespace(): string
    {
        return 'App';
    }
}
