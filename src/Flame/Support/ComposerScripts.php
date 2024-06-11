<?php

declare(strict_types=1);

namespace Flame\Support;

class ComposerScripts
{
    public static function postAutoloadDump(): void
    {
        defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__, 6));
        // 更新ENV
        self::updateENV(BASE_PATH);
        // 更新excel文件
        self::updateCSV(BASE_PATH);
        // 更新migrate文件
        self::updateMigrate(BASE_PATH);
    }

    public static function updateENV(string $rootPath): void
    {
        $file = $rootPath.'/vendor/cakephp/core/functions.php';
        if (file_exists($file)) {
            $c = file_get_contents($file);
            if (stripos($c, 'if (\'false\' === $val) {') === false) {
                $n = <<<'EOF'
if ('false' === $val) {
            $val = false;
        } elseif ('true' === $val) {
            $val = true;
        } elseif ('null' === $val) {
            $val = null;
        }

        if ($val !== null) {
EOF;
                $c = preg_replace('/if \(\$val !== null\) \{/', $n, $c);
                file_put_contents($file, $c);
            }
        }
    }

    public static function updateCSV(string $rootPath): void
    {
        $file = $rootPath.'/vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Reader/Csv.php';
        if (file_exists($file)) {
            $c = file_get_contents($file);
            if (stripos($c, 'version_compare(PHP_VERSION') === false) {
                $s = <<<'EOF'
private function setAutoDetect(?string $value): ?string
    {
EOF;
                $t = <<<'EOF'
private function setAutoDetect(?string $value): ?string
    {
        if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
            return null;
        }
EOF;
                $c = str_replace($s, $t, $c);
                file_put_contents($file, $c);
            }
        }
    }

    public static function updateMigrate(string $rootPath): void
    {
        $closure = function ($command) use ($rootPath) {
            $ucCommand = ucfirst($command);
            $file = $rootPath.'/vendor/robmorgan/phinx/src/Phinx/Console/Command/'.$ucCommand.'.php';
            if (file_exists($file)) {
                $c = file_get_contents($file);
                if (stripos($c, "#[AsCommand(name: '{$command}')]") !== false) {
                    $c = str_replace("#[AsCommand(name: '{$command}')]", "#[AsCommand(name: 'migrate:{$command}')]", $c);
                    $c = str_replace("\$defaultName = '{$command}'", "\$defaultName = 'migrate:{$command}'", $c);
                    file_put_contents($file, $c);
                }
            }
        };

        foreach (['create', 'rollback', 'status'] as $item) {
            $closure($item);
        }

        $file = $rootPath.'/vendor/robmorgan/phinx/src/Phinx/Console/Command/AbstractCommand.php';
        if (file_exists($file)) {
            $c = file_get_contents($file);
            $c = str_replace("'phinx.php'", "'config/phinx.php'", $c);
            file_put_contents($file, $c);
        }
    }
}
