<?php

declare(strict_types=1);

namespace Flame\Support;

use ReflectionClass;
use ReflectionProperty;

trait ArrayHelper
{
    public function setData(array $row): void
    {
        foreach ($row as $col => $val) {
            if (! is_null($val)) {
                $setMethod = 'set'.parse_name($col, 1);
                if (method_exists($this, $setMethod)) {
                    $this->$setMethod($val);
                }
            }
        }
    }

    public function toArray(bool $allProperty = false): array
    {
        return $allProperty ? $this->allProperty() : $this->effectiveProperty();
    }

    public function has(string $property): bool
    {
        return property_exists($this, $property);
    }

    /**
     * 返回对象的全部属性
     */
    protected function getProperties(): array
    {
        $reflect = new ReflectionClass(__CLASS__);

        return $reflect->getProperties(ReflectionProperty::IS_PRIVATE);
    }

    /**
     * 对象的全部属性赋值
     */
    private function allProperty(): array
    {
        $props = $this->getProperties();

        $property = [];
        foreach ($props as $prop) {
            $property[] = $prop->getName();
        }

        $data = [];
        foreach ($property as $p) {
            $data[$p] = $this->$p ?? null;
        }

        return $data;
    }

    /**
     * 返回赋值的对象属性
     */
    private function effectiveProperty(): array
    {
        $data = [];
        foreach ($this as $k => $v) {
            $data[$k] = $v;
        }

        return $data;
    }
}
