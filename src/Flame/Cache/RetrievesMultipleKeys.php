<?php

declare(strict_types=1);

namespace Flame\Cache;

trait RetrievesMultipleKeys
{
    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     */
    public function many($keys)
    {
        $return = [];

        $keys = collect($keys)->mapWithKeys(function ($value, $key) {
            return [is_string($key) ? $key : $value => is_string($key) ? $value : null];
        })->all();

        foreach ($keys as $key => $default) {
            $return[$key] = $this->get($key, $default);
        }

        return $return;
    }

    /**
     * Store multiple items in the cache for a given number of seconds.
     */
    public function putMany($values, $seconds)
    {
        $manyResult = null;

        foreach ($values as $key => $value) {
            $result = $this->put($key, $value, $seconds);

            $manyResult = is_null($manyResult) ? $result : $result && $manyResult;
        }

        return $manyResult ?: false;
    }
}
