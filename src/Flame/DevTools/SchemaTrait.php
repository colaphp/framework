<?php

declare(strict_types=1);

namespace Flame\DevTools;

use Flame\Database\DB;
use Flame\Support\Arr;
use Flame\Support\Str;
use Illuminate\Filesystem\Filesystem;

trait SchemaTrait
{
    private array $ignoreTables = ['migrations'];

    protected function getTables(): array
    {
        $result = DB::select('show tables;');
        $tables = json_decode(json_encode($result), true);
        foreach ($tables as $key => $table) {
            if (in_array($table, $this->ignoreTables)) {
                continue;
            }

            $table = Arr::first($table);
            $tables[$key] = [
                'className' => Str::studly(Str::singular($table)),
                'tableName' => $table,
            ];
        }

        return $tables;
    }

    protected function getTableComment($tableName): string
    {
        $database = env('DB_DATABASE');
        $tableInfo = DB::select("SELECT `TABLE_COMMENT` FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$database' AND TABLE_NAME = '$tableName';");

        return $tableInfo[0]['TABLE_COMMENT'];
    }

    protected function getTableInfo($tableName): array
    {
        $database = env('DB_DATABASE');
        $sql = "SELECT COLUMN_NAME,COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$database' AND TABLE_NAME = '$tableName';";
        $result = DB::select($sql);
        $result = json_decode(json_encode($result), true);

        $comments = [];
        foreach ($result as $row) {
            $comments[$row['COLUMN_NAME']] = $row['COLUMN_COMMENT'];
        }

        $sql = 'desc `'.$tableName.'`';
        $result = DB::select($sql);
        $result = json_decode(json_encode($result), true);

        $columns = [];
        foreach ($result as $row) {
            $row['Comment'] = $comments[$row['Field']];
            $row['BaseType'] = $this->getFieldType($row['Type']);
            $row['SwaggerType'] = $row['BaseType'] === 'int' ? 'integer' : $row['BaseType'];
            $columns[] = $row;
        }

        return $columns;
    }

    protected function getPrimaryKeyType(array $columns): array
    {
        $primaryKey = [];

        foreach ($columns as $column) {
            if ($column['Key'] === 'PRI') {
                $primaryKey = [
                    'Field' => $column['Field'],
                    'Type' => $this->getFieldType($column['Type']),
                ];
                break;
            }
        }

        return $primaryKey;
    }

    protected function getFieldType($type): string
    {
        preg_match('/(\w+)\(/', $type, $m);
        $type = $m[1] ?? $type;
        $type = str_replace(' unsigned', '', $type);
        if (in_array($type, ['bit', 'int', 'bigint', 'mediumint', 'smallint', 'tinyint', 'enum'])) {
            $type = 'int';
        }
        if (in_array($type, ['varchar', 'char', 'text', 'mediumtext', 'longtext'])) {
            $type = 'string';
        }
        if (in_array($type, ['decimal'])) {
            $type = 'float';
        }
        if (in_array($type, ['date', 'datetime', 'timestamp', 'time'])) {
            $type = 'string';
        }

        return $type;
    }

    protected function getSet($field, $type): string
    {
        $capitalName = parse_name($field, 1);

        return <<<EOF
    public function get{$capitalName}(): $type
    {
        return \$this->$field;
    }

    public function set{$capitalName}($type \${$field}): void
    {
        \$this->$field = \${$field};
    }
EOF;
    }

    protected function ensureDirectoryExists(array|string $dirs): void
    {
        $fs = new Filesystem();

        if (is_string($dirs)) {
            $dirs = [$dirs];
        }

        foreach ($dirs as $dir) {
            $fs->ensureDirectoryExists($dir);
        }
    }

    protected function deleteDirectories(string $directory): void
    {
        $fs = new Filesystem();

        $fs->deleteDirectories($directory);
    }
}
