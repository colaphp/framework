<?php

namespace Cola\Contracts\Support;

/**
 * Interface Jsonable
 * @package Cola\Contracts\Support
 */
interface Jsonable
{
    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options
     * @return string
     */
    public function toJson(int $options = 0): string;
}
