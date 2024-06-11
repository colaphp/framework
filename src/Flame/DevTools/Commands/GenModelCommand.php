<?php

declare(strict_types=1);

namespace Flame\DevTools\Commands;

use Flame\DevTools\SchemaTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenModelCommand extends Command
{
    use SchemaTrait;

    protected function configure(): void
    {
        $this->setName('gen:model')
            ->setDescription('Generate model class');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->ensureDirectoryExists([
            app_path('Models'),
        ]);

        $tables = $this->getTables();
        foreach ($tables as $table) {
            $columns = $this->getTableInfo($table['tableName']);

            $this->modelTpl($table['tableName'], $table['className'], $columns);
        }

        return 1;
    }

    private function modelTpl($tableName, $className, $columns): void
    {
        $createdTime = false;
        $updatedTime = false;
        $softDelete = false;

        $primaryKeyStr = '';
        $primaryKey = $this->getPrimaryKeyType($columns);
        if (! empty($primaryKey) && $primaryKey['Field'] !== 'id') {
            $primaryKeyStr = "
    /**
     * 主键
     */
    protected \$primaryKey = '{$primaryKey['Field']}';\n";
        }

        $fieldStr = '';
        foreach ($columns as $column) {
            if ($column['Field'] !== $primaryKey['Field']) {
                $fieldStr .= str_pad(' ', 8)."'{$column['Field']}',\n";
            }
            if ($column['Field'] === 'created_time') {
                $createdTime = true;
            }
            if ($column['Field'] === 'updated_time') {
                $updatedTime = true;
            }
            if ($column['Field'] === 'deleted_time') {
                $softDelete = true;
            }
        }

        $timeText = '';
        $fieldStr = rtrim($fieldStr, "\n");

        $useSoftDelete = '';
        if ($softDelete) {
            $useSoftDelete = "\n    use SoftDeletes;\n";
        }

        $content = file_get_contents(__DIR__.'/stubs/model/model.stub');
        $content = str_replace([
            '{$className}',
            '$tableName',
            '$useSoftDelete',
            '$primaryKeyStr',
            '$timeText',
            '$fieldStr',
        ], [
            $className,
            $tableName,
            $useSoftDelete,
            $primaryKeyStr,
            $timeText,
            $fieldStr,
        ], $content);

        file_put_contents(app_path('Models/'.$className.'Model.php'), $content);
    }
}
