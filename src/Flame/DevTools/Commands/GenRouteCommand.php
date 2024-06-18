<?php

declare(strict_types=1);

namespace Flame\DevTools\Commands;

use Flame\Support\Arr;
use Flame\Support\Str;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class GenRouteCommand extends Command
{
    protected array $ignoreList = ['Base'];

    protected function configure(): void
    {
        $this->setName('gen:route')
            ->setDescription('Generate project routes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $dirs = glob(app_path('API/*'), GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                $module = basename($dir);
                $files = array_merge(
                    glob($dir.'/Controllers/*Controller.php'),
                    glob(app_path('Bundles/*/Controllers/'.$module.'/*Controller.php'))
                );
                $routes = $this->getRoutes($files);
                $this->genRoutes(Str::camel($module), $routes, $dir.'/Routes/route.php');
            }
        } catch (Throwable $e) {
            echo $e->getMessage().PHP_EOL;
        }

        return 0;
    }

    /**
     * @throws ReflectionException
     */
    private function getRoutes(array $files): array
    {
        $routes = [];

        foreach ($files as $file) {
            $file = str_replace('/', '\\', $file);
            preg_match('/(app\\\\.+?\\\\(\w+)Controller)\.php/', $file, $matches);
            if (! in_array($matches[2], $this->ignoreList)) {
                $class = ucfirst($matches[1]);
                $classRoutes = $this->reflectionRoutes($class);
                $routes = array_merge($routes, $classRoutes);
            }
        }

        return $routes;
    }

    /**
     * @throws ReflectionException
     */
    private function reflectionRoutes(string $class): array
    {
        $reflectionClass = new ReflectionClass($class);
        $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);
        $methods = array_filter($methods, function ($item) use ($class) {
            return $item->class === $class;
        });

        $routes = [];
        foreach ($methods as $method) {
            $methodAttributes = $reflectionClass->getMethod($method->name)->getAttributes();
            if (isset($methodAttributes[0])) {
                $methodAttribute = $methodAttributes[0];
                $routes[] = [
                    'httpMethod' => Str::lower(Arr::last(explode('\\', $methodAttribute->getName()))),
                    'path' => $methodAttribute->getArguments()['path'],
                    'class' => $class,
                    'action' => $method->name,
                    'summary' => $methodAttribute->getArguments()['summary'],
                ];
            }
        }

        return $routes;
    }

    private function genRoutes(string $module, array $routes, string $routeFile): void
    {
        $routeContent = '// Route start';
        $routeContent .= "\nRoute::group('/api/v1/{$module}', function () {";
        foreach ($routes as $route) {
            $routeContent .= "\n    // ".$route['summary'];
            $routeContent .= "\n    Route::{$route['httpMethod']}('{$route['path']}', [\\{$route['class']}::class, '{$route['action']}'])";
            // if ($route['httpMethod'] === 'get') {
            //     $name = Str::replace('/', '.', $route['path']);
            //     $routeContent .= "->name('$name')";
            // }
            $routeContent .= ';';
        }
        $routeContent .= "\n});";
        $routeContent .= "\n// end";

        $content = $this->getTemplate($routeContent);
        file_put_contents($routeFile, $content);
    }

    private function getTemplate($content): string
    {
        return <<<EOF
<?php

// ==========================================================================
// Code generated by gen:route CLI tool. DO NOT EDIT.
// ==========================================================================

declare(strict_types=1);

use Flame\Routing\Route;

$content

EOF;
    }
}