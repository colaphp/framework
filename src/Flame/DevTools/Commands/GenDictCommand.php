<?php

declare(strict_types=1);

namespace Flame\DevTools\Commands;

use Flame\DevTools\SchemaTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenDictCommand extends Command
{
    use SchemaTrait;

    protected function configure(): void
    {
        $this->setName('gen:dict')
            ->setDescription('Generate database dict');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $content = "# 数据字典\n\n";

        $tables = $this->getTables();
        foreach ($tables as $table) {
            $tableComment = $this->getTableComment($table['tableName']);
            $content .= "### {$tableComment}(`{$table['tableName']}`)\n";

            $columns = $this->getTableInfo($table['tableName']);
            $content .= $this->getContent($columns);
        }

        file_put_contents(public_path('docs/dict/README.md'), $content);

        return 1;
    }

    public function getContent($columns): string
    {
        $content = "| 列名 | 数据类型 | 索引 | 是否为空 | 描述 |\n";
        $content .= "| ------- | --------- | --------- | --------- | -------------- |\n";
        foreach ($columns as $column) {
            $isNull = $column['Null'] === 'NO' ? '否' : '是';
            $content .= "| {$column['Field']} | {$column['Type']} | {$column['Key']} | $isNull | {$column['Comment']} |\n";
        }
        $content .= "\n";

        return $content;
    }
}
