<?php

namespace Cola\Support;

class ComposerScripts
{
    public static function postAutoloadDump(): void
    {
        self::updateAbstractController();
    }

    public static function updateAbstractController(): void
    {
        $basePath = dirname(__DIR__, 5);
        self::addMigrationPrefix($basePath);
    }

    public static function addMigrationPrefix($basePath): void
    {
        $commands = glob($basePath . '/vendor/robmorgan/phinx/src/Phinx/Console/Command/*.php');
        foreach ($commands as $command) {
            $content = file_get_contents($command);
            preg_match('/protected static \$defaultName = \'(\w+)\';/', $content, $matches);
            if (isset($matches[1])) {
                $content = str_replace('defaultName = \'', 'defaultName = \'migration:', $content);
                file_put_contents($command, $content);
            }
        }
    }
}
