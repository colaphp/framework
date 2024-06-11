<?php

declare(strict_types=1);

namespace Flame\Cache;

use Exception;
use Illuminate\Cache\FileLock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Filesystem\LockTimeoutException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\LockableFile;
use Illuminate\Support\InteractsWithTime;

class FileStore implements LockProvider, Store
{
    use InteractsWithTime, RetrievesMultipleKeys;

    /**
     * The Illuminate Filesystem instance.
     */
    protected Filesystem $files;

    /**
     * The file cache directory.
     */
    protected string $directory;

    /**
     * The file cache lock directory.
     */
    protected ?string $lockDirectory;

    /**
     * Octal representation of the cache file permissions.
     */
    protected ?int $filePermission;

    /**
     * Create a new file cache store instance.
     */
    public function __construct(Filesystem $files, string $directory, ?int $filePermission = null)
    {
        $this->files = $files;
        $this->directory = $directory;
        $this->filePermission = $filePermission;
    }

    /**
     * Retrieve an item from the cache by key.
     */
    public function get($key): mixed
    {
        return $this->getPayload($key)['data'] ?? null;
    }

    /**
     * Store an item in the cache for a given number of seconds.
     */
    public function put($key, $value, $seconds): bool
    {
        $this->ensureCacheDirectoryExists($path = $this->path($key));

        $result = $this->files->put(
            $path, $this->expiration($seconds).serialize($value), true
        );

        if ($result !== false && $result > 0) {
            $this->ensurePermissionsAreCorrect($path);

            return true;
        }

        return false;
    }

    /**
     * Store an item in the cache if the key doesn't exist.
     */
    public function add($key, $value, $seconds): bool
    {
        $this->ensureCacheDirectoryExists($path = $this->path($key));

        $file = new LockableFile($path, 'c+');

        try {
            $file->getExclusiveLock();
        } catch (LockTimeoutException) {
            $file->close();

            return false;
        }

        $expire = $file->read(10);

        if (empty($expire) || $this->currentTime() >= $expire) {
            $file->truncate()
                ->write($this->expiration($seconds).serialize($value))
                ->close();

            $this->ensurePermissionsAreCorrect($path);

            return true;
        }

        $file->close();

        return false;
    }

    /**
     * Create the file cache directory if necessary.
     */
    protected function ensureCacheDirectoryExists($path)
    {
        $directory = dirname($path);

        if (! $this->files->exists($directory)) {
            $this->files->makeDirectory($directory, 0777, true, true);

            // We're creating two levels of directories (e.g. 7e/24), so we check them both...
            $this->ensurePermissionsAreCorrect($directory);
            $this->ensurePermissionsAreCorrect(dirname($directory));
        }
    }

    /**
     * Ensure the created node has the correct permissions.
     */
    protected function ensurePermissionsAreCorrect($path)
    {
        if (is_null($this->filePermission) ||
            intval($this->files->chmod($path), 8) == $this->filePermission) {
            return;
        }

        $this->files->chmod($path, $this->filePermission);
    }

    /**
     * Increment the value of an item in the cache.
     */
    public function increment($key, $value = 1)
    {
        $raw = $this->getPayload($key);

        return tap(((int) $raw['data']) + $value, function ($newValue) use ($key, $raw) {
            $this->put($key, $newValue, $raw['time'] ?? 0);
        });
    }

    /**
     * Decrement the value of an item in the cache.
     */
    public function decrement($key, $value = 1)
    {
        return $this->increment($key, $value * -1);
    }

    /**
     * Store an item in the cache indefinitely.
     */
    public function forever($key, $value)
    {
        return $this->put($key, $value, 0);
    }

    /**
     * Get a lock instance.
     */
    public function lock($name, $seconds = 0, $owner = null)
    {
        $this->ensureCacheDirectoryExists($this->lockDirectory ?? $this->directory);

        return new FileLock(
            new static($this->files, $this->lockDirectory ?? $this->directory, $this->filePermission),
            $name,
            $seconds,
            $owner
        );
    }

    /**
     * Restore a lock instance using the owner identifier.
     */
    public function restoreLock($name, $owner)
    {
        return $this->lock($name, 0, $owner);
    }

    /**
     * Remove an item from the cache.
     */
    public function forget($key)
    {
        if ($this->files->exists($file = $this->path($key))) {
            return $this->files->delete($file);
        }

        return false;
    }

    /**
     * Remove all items from the cache.
     */
    public function flush()
    {
        if (! $this->files->isDirectory($this->directory)) {
            return false;
        }

        foreach ($this->files->directories($this->directory) as $directory) {
            $deleted = $this->files->deleteDirectory($directory);

            if (! $deleted || $this->files->exists($directory)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Retrieve an item and expiry time from the cache by key.
     */
    protected function getPayload($key)
    {
        $path = $this->path($key);

        // If the file doesn't exist, we obviously cannot return the cache so we will
        // just return null. Otherwise, we'll get the contents of the file and get
        // the expiration UNIX timestamps from the start of the file's contents.
        try {
            if (is_null($contents = $this->files->get($path, true))) {
                return $this->emptyPayload();
            }

            $expire = substr($contents, 0, 10);
        } catch (Exception) {
            return $this->emptyPayload();
        }

        // If the current time is greater than expiration timestamps we will delete
        // the file and return null. This helps clean up the old files and keeps
        // this directory much cleaner for us as old files aren't hanging out.
        if ($this->currentTime() >= $expire) {
            $this->forget($key);

            return $this->emptyPayload();
        }

        try {
            $data = unserialize(substr($contents, 10));
        } catch (Exception) {
            $this->forget($key);

            return $this->emptyPayload();
        }

        // Next, we'll extract the number of seconds that are remaining for a cache
        // so that we can properly retain the time for things like the increment
        // operation that may be performed on this cache on a later operation.
        $time = $expire - $this->currentTime();

        return compact('data', 'time');
    }

    /**
     * Get a default empty payload for the cache.
     */
    protected function emptyPayload()
    {
        return ['data' => null, 'time' => null];
    }

    /**
     * Get the full path for the given cache key.
     */
    public function path($key)
    {
        $parts = array_slice(str_split($hash = sha1($key), 2), 0, 2);

        return $this->directory.'/'.implode('/', $parts).'/'.$hash;
    }

    /**
     * Get the expiration time based on the given seconds.
     */
    protected function expiration($seconds)
    {
        $time = $this->availableAt($seconds);

        return $seconds === 0 || $time > 9999999999 ? 9999999999 : $time;
    }

    /**
     * Get the Filesystem instance.
     */
    public function getFilesystem(): Filesystem
    {
        return $this->files;
    }

    /**
     * Get the working directory of the cache.
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * Set the cache directory where locks should be stored.
     */
    public function setLockDirectory(?string $lockDirectory): static
    {
        $this->lockDirectory = $lockDirectory;

        return $this;
    }

    /**
     * Get the cache key prefix.
     */
    public function getPrefix(): string
    {
        return '';
    }
}
