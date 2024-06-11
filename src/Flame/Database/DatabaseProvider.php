<?php

declare(strict_types=1);

namespace Flame\Database;

use Flame\Container\Container;
use Flame\Contracts\Bootstrap;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\MySqlConnection;
use Illuminate\Events\Dispatcher;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\Paginator;
use Throwable;
use Workerman\Timer;
use Workerman\Worker;

class DatabaseProvider implements Bootstrap
{
    public static function start(?Worker $worker): void
    {
        if (! class_exists(Capsule::class)) {
            return;
        }

        $config = config('database', []);
        $connections = $config['connections'] ?? [];
        if (! $connections) {
            return;
        }

        $capsule = new Capsule(IlluminateContainer::getInstance());

        $default = $config['default'] ?? false;
        if ($default) {
            $defaultConfig = $connections[$config['default']] ?? false;
            if ($defaultConfig) {
                $capsule->addConnection($defaultConfig);
            }
        }

        foreach ($connections as $name => $config) {
            $capsule->addConnection($config, $name);
        }

        if (class_exists(Dispatcher::class) && ! $capsule->getEventDispatcher()) {
            $capsule->setEventDispatcher(Container::make(Dispatcher::class, [IlluminateContainer::getInstance()]));
        }

        $capsule->setAsGlobal();

        $capsule->bootEloquent();

        // Heartbeat
        if ($worker) {
            Timer::add(55, function () use ($capsule) {
                foreach ($capsule->getDatabaseManager()->getConnections() as $connection) {
                    /* @var MySqlConnection $connection **/
                    if ($connection->getConfig('driver') === 'mysql') {
                        try {
                            $connection->select('select 1');
                        } catch (Throwable $e) {
                        }
                    }
                }
            });
        }

        // Paginator
        if (class_exists(Paginator::class)) {
            if (method_exists(Paginator::class, 'queryStringResolver')) {
                Paginator::queryStringResolver(function () {
                    return request()->queryString();
                });
            }
            Paginator::currentPathResolver(function () {
                return request()->path();
            });
            Paginator::currentPageResolver(function ($pageName = 'page') {
                $page = intval(request()->input($pageName, 1));

                return $page > 0 ? $page : 1;
            });
            if (class_exists(CursorPaginator::class)) {
                CursorPaginator::currentCursorResolver(function ($cursorName = 'cursor') {
                    return Cursor::fromEncoded(request()->input($cursorName));
                });
            }
        }
    }
}
