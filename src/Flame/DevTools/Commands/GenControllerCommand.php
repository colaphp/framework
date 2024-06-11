<?php

declare(strict_types=1);

namespace Flame\DevTools\Commands;

use Flame\DevTools\SchemaTrait;
use Flame\Support\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenControllerCommand extends Command
{
    use SchemaTrait;

    protected function configure(): void
    {
        $this->setName('gen:controller')
            ->setDescription('Generate controller class');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (is_dir(app_path('Controllers'))) {
            $this->deleteDirectories(app_path('Controllers'));
        }

        $this->ensureDirectoryExists([
            app_path('Controllers'),
            app_path('Controllers/Request'),
            app_path('Controllers/Response'),
        ]);

        $tables = $this->getTables();
        foreach ($tables as $table) {
            $comment = $this->getTableComment($table['tableName']);
            if (Str::endsWith($comment, '表')) {
                $comment = Str::substr($comment, 0, -1);
            }
            $comment .= '模块';
            $columns = $this->getTableInfo($table['tableName']);

            $this->controllerTpl($table['className'], $comment);
            $this->requestTpl($table['className'], $columns);
            $this->responseTpl($table['className'], $columns);
        }

        return 1;
    }

    private function controllerTpl(string $name, string $comment): void
    {
        $content = file_get_contents(__DIR__.'/stubs/controller/controller.stub');
        $content = str_replace([
            '{$camelName}',
            '{$name}',
            '{$comment}',
        ], [
            Str::camel($name),
            $name,
            $comment,
        ], $content);
        file_put_contents(app_path('Controllers/'.$name.'Controller.php'), $content);
    }

    private function requestTpl(string $name, array $columns): void
    {
        $dist = app_path('Controllers/Request/'.Str::camel($name));
        if (! is_dir($dist)) {
            $this->ensureDirectoryExists($dist);
        }

        $ignoreFields = [
            'created_time',
            'updated_time',
            'deleted_time',
        ];

        $dataSets = ['required' => '', 'properties' => '', 'rule' => '', 'message' => ''];
        foreach ($columns as $column) {
            if (in_array($column['Field'], $ignoreFields)) {
                continue;
            }
            if ($column['Field'] === 'default') {
                $column['Field'] = 'isDefault';
            }
            if ($column['Field'] === 'id' && empty($column['Comment'])) {
                $column['Comment'] = 'ID';
            }
            $dataSets['required'] .= "        '".$column['Field']."',\n";
            $dataSets['properties'] .= "        new OA\Property(property: '{$column['Field']}', description: '{$column['Comment']}', type: '{$column['SwaggerType']}'),\n";
            $dataSets['rule'] .= "        '{$column['Field']}' => 'require',\n";
            $dataSets['message'] .= "        '{$column['Field']}.require' => '请设置{$column['Comment']}',\n";
        }

        $dataSets = array_map(function ($item) {
            return rtrim($item, "\n");
        }, $dataSets);

        $content = file_get_contents(__DIR__.'/stubs/controller/request/create.stub');
        $content = str_replace([
            '{$camelName}',
            '{$name}',
            '{$dataSets[required]}',
            '{$dataSets[properties]}',
            '{$dataSets[rule]}',
            '{$dataSets[message]}',
        ], [
            Str::camel($name),
            $name,
            $dataSets['required'],
            $dataSets['properties'],
            $dataSets['rule'],
            $dataSets['message'],
        ], $content);
        file_put_contents(app_path('Controllers/Request/'.Str::camel($name).'/'.$name.'CreateRequest.php'), $content);

        $content = file_get_contents(__DIR__.'/stubs/controller/request/query.stub');
        $content = str_replace([
            '{$camelName}',
            '{$name}',
            '{$dataSets[required]}',
            '{$dataSets[properties]}',
            '{$dataSets[rule]}',
            '{$dataSets[message]}',
        ], [
            Str::camel($name),
            $name,
            '',
            '',
            '',
            '',
        ], $content);
        file_put_contents(app_path('Controllers/Request/'.Str::camel($name).'/'.$name.'QueryRequest.php'), $content);

        $content = file_get_contents(__DIR__.'/stubs/controller/request/update.stub');
        $content = str_replace([
            '{$camelName}',
            '{$name}',
            '{$dataSets[required]}',
            '{$dataSets[properties]}',
            '{$dataSets[rule]}',
            '{$dataSets[message]}',
        ], [
            Str::camel($name),
            $name,
            $dataSets['required'],
            $dataSets['properties'],
            $dataSets['rule'],
            $dataSets['message'],
        ], $content);
        file_put_contents(app_path('Controllers/Request/'.Str::camel($name).'/'.$name.'UpdateRequest.php'), $content);
    }

    private function responseTpl(string $name, array $columns): void
    {
        $dist = app_path('controller/response/'.Str::camel($name));
        if (! is_dir($dist)) {
            $this->ensureDirectoryExists($dist);
        }

        $content = file_get_contents(__DIR__.'/stubs/controller/response/query.stub');
        $content = str_replace([
            '{$camelName}',
            '{$name}',
        ], [
            Str::camel($name),
            $name,
        ], $content);
        file_put_contents(app_path('Controllers/Response/'.Str::camel($name).'/'.$name.'QueryResponse.php'), $content);

        $ignoreFields = ['deleted_time', 'password', 'password_salt'];

        $fields = "\n";
        foreach ($columns as $column) {
            if (in_array($column['Field'], $ignoreFields)) {
                continue;
            }

            if ($column['Field'] === 'default') {
                $column['Field'] = 'isDefault';
            }
            if ($column['Field'] === 'id' && empty($column['Comment'])) {
                $column['Comment'] = 'ID';
            }
            $fields .= "    #[OA\Property(property: '{$column['Field']}', description: '{$column['Comment']}', type: '{$column['SwaggerType']}')]\n";
            $fields .= '    private '.$column['BaseType'].' $'.parse_name($column['Field'], 1, false).";\n\n";
        }

        foreach ($columns as $column) {
            if (in_array($column['Field'], $ignoreFields)) {
                continue;
            }

            $fields .= $this->getSet(parse_name($column['Field'], 1, false), $column['BaseType'])."\n\n";
        }

        $fields = rtrim($fields, "\n");

        $content = file_get_contents(__DIR__.'/stubs/controller/response/response.stub');
        $content = str_replace([
            '{$camelName}',
            '{$name}',
            '{$fields}',
        ], [
            Str::camel($name),
            $name,
            $fields,
        ], $content);
        file_put_contents(app_path('Controllers/Response/'.Str::camel($name).'/'.$name.'Response.php'), $content);
    }
}
