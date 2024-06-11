<?php

declare(strict_types=1);

namespace Flame\Bootstrap;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidFileException;
use Symfony\Component\Console\Output\ConsoleOutput;

class LoadEnvironmentVariables
{
    /**
     * 环境变量文件的目录
     */
    protected string $filePath;

    /**
     * 环境变量文件的名称
     */
    protected ?string $fileName;

    /**
     * 创建一个新的加载环境变量实例
     */
    public function __construct(string $path, ?string $name = null)
    {
        $this->filePath = $path;
        $this->fileName = $name;
    }

    /**
     * 设置环境变量
     *
     * 如果不存在环境文件，则静默地继续
     */
    public function bootstrap(): void
    {
        try {
            $this->createDotenv()->safeLoad();
        } catch (InvalidFileException $e) {
            $this->writeErrorAndDie([
                '环境变量文件无效',
                $e->getMessage(),
            ]);
        }
    }

    /**
     * 创建一个Dotenv实例
     */
    protected function createDotenv(): Dotenv
    {
        if (method_exists('Dotenv\Dotenv', 'createUnsafeImmutable')) {
            return Dotenv::createUnsafeImmutable($this->filePath, $this->fileName);
        } else {
            return Dotenv::createMutable($this->filePath, $this->fileName);
        }
    }

    /**
     * 写入错误信息并退出
     */
    protected function writeErrorAndDie(array $errors): void
    {
        $output = (new ConsoleOutput)->getErrorOutput();

        foreach ($errors as $error) {
            $output->writeln($error);
        }

        exit(1);
    }
}
