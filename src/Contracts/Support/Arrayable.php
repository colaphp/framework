<?php

namespace Cola\Contracts\Support;

/**
 * Interface Arrayable
 * @package Cola\Contracts\Support
 */
interface Arrayable
{
    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array;
}
