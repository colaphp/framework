<?php

declare(strict_types=1);

namespace Flame\Console\Commands;

use Flame\Support\AnnotationHelper;
use ReflectionException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenErrorCodeCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('gen:error-code')
            ->setDescription('Generate error code.');
    }

    /**
     * @throws ReflectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $enumFiles = glob(app_path('Bundles/*/Enums/*ErrorCodeEnum.php'));

        $errorCodes = [];

        $content = "# ERROR CODE\n\n";
        $content .= "不要编辑这个文件，它的内容是由`gen:error-code`命令生成.\n\n";
        foreach ($enumFiles as $file) {
            preg_match('/\/Bundles\/(.*)\/Enums\/(.*)\.php/', $file, $matches);
            if (isset($matches[2])) {
                $enumClass = '\\App\\Bundles\\'.$matches[1].'\\Enums\\'.$matches[2];

                $annotationHelper = new AnnotationHelper();
                $reflectionEnums = $annotationHelper->getReflectionEnums($enumClass);

                $content .= "## {$matches[1]}\n\n";
                $content .= "```php\n";
                foreach ($reflectionEnums as $key => $enum) {
                    if (isset($errorCodes[$enum['val']->value])) {
                        exit(sprintf('%s 错误码[%d]存在重复.', $matches[2], $enum['val']->value).PHP_EOL);
                    }

                    $content .= ($key > 0 ? "\n\n" : '')."// {$enum['name']}\n";
                    $content .= "{$enum['val']->name} => {$enum['val']->value}";

                    $errorCodes[$enum['val']->value] = 1;
                }
                $content .= "\n```\n\n";
            }
        }

        file_put_contents(app_path('Bundles/ERROR_CODE.md'), $content);

        return 0;
    }
}
