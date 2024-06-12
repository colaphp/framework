<?php

declare(strict_types=1);

namespace Flame\DevTools\Commands;

use Flame\DevTools\SchemaTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenRepositoryCommand extends Command
{
    use SchemaTrait;

    protected function configure(): void
    {
        $this->setName('gen:dao')
            ->setDescription('Generate repository class');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->ensureDirectoryExists([
            app_path('Repositories'),
        ]);

        $tables = $this->getTables();
        foreach ($tables as $table) {
            $columns = $this->getTableInfo($table['tableName']);
            $primaryKey = $this->getPrimaryKeyType($columns);

            $this->repositoryTpl($table['tableName'], $table['className'], $primaryKey);
        }

        return 1;
    }

    private function repositoryTpl(string $tableName, string $name, array $primaryKey): void
    {
        $primaryKeyType = empty($primaryKey) ? 'int' : $primaryKey['Type'];

        $content = file_get_contents(__DIR__.'/stubs/repository/repository.stub');
        $content = str_replace([
            '{$name}',
            '{$tableName}',
            '{$primaryKeyType}',
        ], [
            $name,
            $tableName,
            $primaryKeyType,
        ], $content);
        file_put_contents(app_path('Repositories/'.$name.'Repository.php'), $content);
    }
}
