<?php

declare(strict_types=1);

namespace Flame\Cache;

use Flame\Redis\RedisManager;
use Flame\Support\Arr;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as RepositoryContract;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;

/**
 * @mixin Repository
 * @mixin LockProvider
 */
class CacheFactory extends CacheManager implements Factory
{
    private $config;

    public function __construct()
    {
        $this->config = config('cache');
    }

    /**
     * Get a cache store instance by name, wrapped in a repository.
     */
    public function store($name = null): RepositoryContract
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->stores[$name] ??= $this->resolve($name);
    }

    /**
     * Get a cache driver instance.
     */
    public function driver($driver = null): RepositoryContract
    {
        return $this->store($driver);
    }

    /**
     * Resolve the given store.
     *
     * @throws InvalidArgumentException
     */
    public function resolve($name): RepositoryContract
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Cache store [{$name}] is not defined.");
        }

        $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($config);
        }

        throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
    }

    /**
     * Create an instance of the Null cache driver.
     */
    protected function createNullDriver(): Repository
    {
        return $this->repository(new NullStore);
    }

    /**
     * Create an instance of the file cache driver.
     */
    protected function createFileDriver(array $config): Repository
    {
        return $this->repository(
            (new FileStore(new Filesystem(), $config['path'], $config['permission'] ?? null))
                ->setLockDirectory($config['lock_path'] ?? null),
            $config
        );
    }

    /**
     * Create an instance of the Redis cache driver.
     */
    protected function createRedisDriver(array $config): Repository
    {
        $redis = RedisManager::instance();

        $connection = $config['connection'] ?? 'default';

        $store = new RedisStore($redis, $this->getPrefix($config), $connection);

        return $this->repository(
            $store->setLockConnection($config['lock_connection'] ?? $connection),
            $config
        );
    }

    /**
     * Create a new cache repository with the given implementation.
     */
    public function repository(Store $store, array $config = []): Repository
    {
        return new Repository($store, Arr::only($config, ['store']));
    }

    /**
     * Get the cache prefix.
     */
    protected function getPrefix(array $config): string
    {
        return $config['prefix'] ?? $this->config['prefix'];
    }

    /**
     * Get the cache connection configuration.
     */
    protected function getConfig($name): ?array
    {
        if (! is_null($name) && $name !== 'null') {
            return $this->config['stores'][$name];
        }

        return ['driver' => 'null'];
    }

    /**
     * Get the default cache driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->config['default'];
    }

    /**
     * Set the default cache driver name.
     */
    public function setDefaultDriver($name): void
    {
        $this->config['default'] = $name;
    }

    /**
     * Unset the given driver instances.
     */
    public function forgetDriver($name = null): static
    {
        $name ??= $this->getDefaultDriver();

        foreach ((array) $name as $cacheName) {
            if (isset($this->stores[$cacheName])) {
                unset($this->stores[$cacheName]);
            }
        }

        return $this;
    }

    /**
     * Disconnect the given driver and remove from local cache.
     */
    public function purge($name = null): void
    {
        $name ??= $this->getDefaultDriver();

        unset($this->stores[$name]);
    }

    /**
     * Dynamically call the default driver instance.
     */
    public function __call($method, $parameters)
    {
        return $this->store()->$method(...$parameters);
    }
}
