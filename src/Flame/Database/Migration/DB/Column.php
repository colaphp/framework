<?php

declare(strict_types=1);

namespace Flame\Database\Migration\DB;

use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Db\Table\Column as BaseColumn;

class Column extends BaseColumn
{
    protected bool $unique = false;

    public function setNullable(): Column
    {
        return $this->setNull(true);
    }

    public function setUnsigned(): Column
    {
        return $this->setSigned(false);
    }

    public function setUnique(): Column
    {
        $this->unique = true;

        return $this;
    }

    public function getUnique(): bool
    {
        return $this->unique;
    }

    public function isUnique(): bool
    {
        return $this->getUnique();
    }

    public static function make($name, $type, $options = []): Column
    {
        $column = new self();
        $column->setName($name);
        $column->setType($type);
        $column->setOptions($options);

        return $column;
    }

    public static function bigInteger($name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_BIG_INTEGER);
    }

    public static function binary($name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_BLOB);
    }

    public static function boolean($name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_BOOLEAN);
    }

    public static function char($name, $length = 255): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_CHAR, compact('length'));
    }

    public static function date($name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_DATE);
    }

    public static function dateTime($name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_DATETIME);
    }

    public static function decimal($name, $precision = 10, $scale = 2): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_DECIMAL, compact('precision', 'scale'));
    }

    public static function enum($name, array $values): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_ENUM, compact('values'));
    }

    public static function float($name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_FLOAT);
    }

    public static function integer($name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_INTEGER);
    }

    public static function json($name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_JSON);
    }

    public static function jsonb($name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_JSONB);
    }

    public static function longText($name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_TEXT, ['length' => MysqlAdapter::TEXT_LONG]);
    }

    public static function mediumInteger($name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_INTEGER, ['length' => MysqlAdapter::INT_MEDIUM]);
    }

    public static function mediumText($name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_TEXT, ['length' => MysqlAdapter::TEXT_MEDIUM]);
    }

    public static function smallInteger($name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_INTEGER, ['length' => MysqlAdapter::INT_SMALL]);
    }

    public static function string($name, $length = 255): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_STRING, compact('length'));
    }

    public static function text($name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_TEXT);
    }

    public static function time($name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_TIME);
    }

    public static function tinyInteger($name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_INTEGER, ['length' => MysqlAdapter::INT_TINY]);
    }

    public static function unsignedBigInteger($name): Column
    {
        return self::bigInteger($name)->setUnSigned();
    }

    public static function unsignedInteger($name): Column
    {
        return self::integer($name)->setUnSigned();
    }

    public static function unsignedTinyInteger($name): Column
    {
        return self::tinyInteger($name)->setUnSigned();
    }

    public static function timestamp($name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_TIMESTAMP);
    }

    public static function uuid($name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_UUID);
    }
}
