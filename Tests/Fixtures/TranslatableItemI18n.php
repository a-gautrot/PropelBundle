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
 * # TranslatableItemI18n
 */
class TranslatableItemI18n implements ActiveRecordInterface
{
    /**
     * @var mixed|null
     */
    private mixed $id;

    /**
     * @var mixed|null
     */
    private mixed $locale;

    /**
     * @var mixed|null
     */
    private mixed $value;

    /**
     * @var mixed
     */
    private mixed $value2;

    /**
     * @var mixed
     */
    private mixed $item;

    /**
     * @param mixed $id
     * @param mixed $locale
     * @param mixed $value
     */
    public function __construct(mixed $id = null, mixed $locale = null, mixed $value = null)
    {
        $this->id = $id;
        $this->locale = $locale;
        $this->value = $value;
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

    /**
     * @return mixed|null
     */
    public function getLocale(): mixed
    {
        return $this->locale;
    }

    /**
     * @param mixed $locale
     * @return void
     */
    public function setLocale(mixed $locale = null): void
    {
        $this->locale = $locale;
    }

    /**
     * @return mixed
     */
    public function getItem(): mixed
    {
        return $this->item;
    }

    /**
     * @param mixed $item
     * @return void
     */
    public function setItem(mixed $item): void
    {
        $this->item = $item;
    }

    /**
     * @return mixed|null
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     * @return void
     */
    public function setValue(mixed $value = null): void
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue2(): mixed
    {
        return $this->value2;
    }

    /**
     * @param mixed $value2
     * @return void
     */
    public function setValue2(mixed $value2): void
    {
        $this->value2 = $value2;
    }
}
