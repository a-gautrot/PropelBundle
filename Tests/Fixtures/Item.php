<?php

/**
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
     * @param mixed $id
     * @param mixed $value
     * @param mixed $groupName
     * @param mixed $price
     */
    public function __construct(private mixed $id = null, private readonly mixed $value = null, private readonly mixed $groupName = null, private readonly mixed $price = null)
    {
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @return mixed
     */
    public function getGroupName(): mixed
    {
        return $this->groupName;
    }

    /**
     * @return mixed
     */
    public function getPrice(): mixed
    {
        return $this->price;
    }

    /**
     * @return mixed
     */
    public function getPrimaryKey(): mixed
    {
        return $this->getId();
    }

    /**
     * @return mixed
     */
    public function getId(): mixed
    {
        return $this->id;
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
     * @param mixed $id
     * @return void
     */
    public function setId(mixed $id = null): void
    {
        $this->id = $id;
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
