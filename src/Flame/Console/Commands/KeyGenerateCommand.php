<?php

declare(strict_types=1);

namespace Flame\Console\Commands;

use Exception;
use Flame\Encryption\Encrypter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class KeyGenerateCommand extends Command
{
    private array $config;

    protected function configure(): void
    {
        $this->setName('key:generate')
            ->setDescription('Set the application key');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->config = config('app');

        $key = $this->generateRandomKey();

        // Next, we will replace the application key in the environment file so it is
        // automatically setup for this developer. This key gets generated using a
        // secure random byte generator and is later base64 encoded for storage.
        if (! $this->setKeyInEnvironmentFile($key, $output)) {
            return 1;
        }

        return 0;
    }

    /**
     * Generate a random key for the application.
     *
     * @throws Exception
     */
    protected function generateRandomKey(): string
    {
        return 'base64:'.base64_encode(
            Encrypter::generateKey($this->config['cipher'])
        );
    }

    /**
     * Set the application key in the environment file.
     */
    protected function setKeyInEnvironmentFile(string $key, OutputInterface $output): bool
    {
        $currentKey = $this->config['key'];

        if (strlen($currentKey) !== 0) {
            return false;
        }

        if (! $this->writeNewEnvironmentFileWith($key, $output)) {
            return false;
        }

        return true;
    }

    /**
     * Write a new environment file with the given key.
     */
    protected function writeNewEnvironmentFileWith(string $key, OutputInterface $output): bool
    {
        $replaced = preg_replace(
            $this->keyReplacementPattern(),
            'APP_KEY='.$key,
            $input = file_get_contents(base_path('.env'))
        );

        if ($replaced === $input || $replaced === null) {
            $output->writeln('Unable to set application key. No APP_KEY variable was found in the .env file.');

            return false;
        }

        file_put_contents(base_path('.env'), $replaced);

        return true;
    }

    /**
     * Get a regex pattern that will match env APP_KEY with any random key.
     */
    protected function keyReplacementPattern(): string
    {
        $escaped = preg_quote('='.$this->config['key'], '/');

        return "/^APP_KEY{$escaped}/m";
    }
}
