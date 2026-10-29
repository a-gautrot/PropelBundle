<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Bundle\PropelBundle\Tests\Fixtures;

use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * # Item
 */
class Item implements ActiveRecordInterface
{
    /**
     * @var mixed|null
     */
    private mixed $id;

    /**
     * @var mixed|null
     */
    private mixed $value;

    /**
     * @var mixed|null
     */
    private mixed $groupName;

    /**
     * @var mixed|null
     */
    private mixed $price;

    /**
     * @param mixed $id
     * @param mixed $value
     * @param mixed $groupName
     * @param mixed $price
     */
    public function __construct(mixed $id = null, mixed $value = null, mixed $groupName = null, mixed $price = null)
    {
        $this->id = $id;
        $this->value = $value;
        $this->groupName = $groupName;
        $this->price = $price;
    }

    /**
     * @return mixed|null
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @return mixed|null
     */
    public function getGroupName(): mixed
    {
        return $this->groupName;
    }

    /**
     * @return mixed|null
     */
    public function getPrice(): mixed
    {
        return $this->price;
    }

    /**
     * @return mixed|null
     */
    public function getPrimaryKey(): mixed
    {
        return $this->getId();
    }

    /**
     * @return mixed|null
     */
    public function getId(): mixed
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return void
     */
    public function setId(mixed $id = null): void
    {
        $this->id = $id;
    }

    /**
     * @param mixed $primaryKey
     * @return void
     */
    public function setPrimaryKey(mixed $primaryKey = null): void
    {
        $this->setId($primaryKey);
    }

    /**
     * @return bool
     */
    public function isPrimaryKeyNull(): bool
    {
        return $this->getId() === null;
    }

    /**
     * @return bool
     */
    public function isModified(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isColumnModified(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isNew(): bool
    {
        return false;
    }

    /**
     * @param bool $b
     * @return void
     */
    public function setNew(bool $b): void
    {
    }

    /**
     * @return void
     */
    public function resetModified(): void
    {
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return false;
    }

    /**
     * @param bool $b
     * @return void
     */
    public function setDeleted(bool $b): void
    {
    }

    /**
     * @param ConnectionInterface|null $con
     * @return void
     */
    public function delete(?ConnectionInterface $con = null): void
    {
    }

    /**
     * @param ConnectionInterface|null $con
     * @return void
     */
    public function save(?ConnectionInterface $con = null): void
    {
    }
}
