<?php

declare(strict_types=1);

namespace Flame\Translation;

use FilesystemIterator;
use Flame\Foundation\Exception\NotFoundException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Symfony\Component\Translation\Translator;

/**
 * @mixin Translator
 */
class Translation
{
    protected static ?Translator $instance = null;

    public static function instance(): Translator
    {
        if (empty(static::$instance)) {
            $config = config('translation', []);
            // Phar support. Compatible with the 'realpath' function in the phar file.
            if (! $translationsPath = get_realpath($config['path'])) {
                throw new NotFoundException("File {$config['path']} not found");
            }

            static::$instance = $translator = new Translator($config['locale']);
            $translator->setFallbackLocales($config['fallback_locale']);

            $classes = [
                'Symfony\Component\Translation\Loader\PhpFileLoader' => [
                    'extension' => '.php',
                    'format' => 'phpfile',
                ],
                'Symfony\Component\Translation\Loader\PoFileLoader' => [
                    'extension' => '.po',
                    'format' => 'pofile',
                ],
            ];

            foreach ($classes as $class => $opts) {
                $translator->addLoader($opts['format'], new $class);
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($translationsPath, FilesystemIterator::SKIP_DOTS));
                $files = new RegexIterator($iterator, '/^.+'.preg_quote($opts['extension']).'$/i', RegexIterator::GET_MATCH);
                foreach ($files as $file) {
                    $file = $file[0];
                    $domain = basename($file, $opts['extension']);
                    $dirName = pathinfo($file, PATHINFO_DIRNAME);
                    $locale = substr(strrchr($dirName, DIRECTORY_SEPARATOR), 1);
                    if ($domain && $locale) {
                        $translator->addResource($opts['format'], $file, $locale, $domain);
                    }
                }
            }
        }

        return static::$instance;
    }

    public static function __callStatic(string $name, array $arguments)
    {
        return static::instance()->{$name}(...$arguments);
    }
}
