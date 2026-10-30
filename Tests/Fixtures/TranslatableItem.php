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
 * # TranslatableItem
 */
class TranslatableItem implements ActiveRecordInterface
{
    /**
     * @var mixed
     */
    private $id;

    /**
     * @var mixed
     */
    private $currentTranslations;

    /**
     * @var mixed
     */
    private $groupName;

    /**
     * @var mixed
     */
    private $price;

    /**
     * @param mixed $id
     * @param mixed $translations
     */
    public function __construct($id = null, $translations = [])
    {
        $this->id = $id;
        $this->currentTranslations = $translations;
    }

    /**
     * @return mixed
     */
    public function getGroupName()
    {
        return $this->groupName;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return mixed
     */
    public function getPrimaryKey()
    {
        return $this->getId();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return void
     */
    public function setId($id = null): void
    {
        $this->id = $id;
    }

    /**
     * @param mixed $primaryKey
     * @return void
     */
    public function setPrimaryKey($primaryKey = null): void
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
     * @param mixed $locale
     * @param ConnectionInterface|null $con
     * @return mixed
     */
    public function getTranslation($locale = 'de', ?ConnectionInterface $con = null)
    {
        if (!isset($this->currentTranslations[$locale])) {
            $translation = new TranslatableItemI18n();
            $translation->setLocale($locale);
            $this->currentTranslations[$locale] = $translation;
        }

        return $this->currentTranslations[$locale];
    }

    /**
     * @param TranslatableItemI18n $i
     * @return void
     */
    public function addTranslatableItemI18n(TranslatableItemI18n $i): void
    {
        if (!in_array($i, $this->currentTranslations)) {
            $this->currentTranslations[$i->getLocale()] = $i;
            $i->setItem($this);
        }
    }

    /**
     * @param TranslatableItemI18n $i
     * @return void
     */
    public function removeTranslatableItemI18n(TranslatableItemI18n $i): void
    {
        unset($this->currentTranslations[$i->getLocale()]);
    }

    /**
     * @return mixed
     */
    public function getTranslatableItemI18ns()
    {
        return $this->currentTranslations;
    }
}
