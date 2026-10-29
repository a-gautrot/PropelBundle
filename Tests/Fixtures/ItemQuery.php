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

use Propel\Generator\Model\PropelTypes;
use Propel\Runtime\Map\ColumnMap;
use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\Map\TableMap;

/**
 * # ItemQuery
 */
class ItemQuery
{
    /**
     * @var array
     */
    private array $map = [
        'id' => PropelTypes::INTEGER,
        'value' => PropelTypes::VARCHAR,
        'code' => PropelTypes::CHAR,
        'price' => PropelTypes::FLOAT,
        'is_active' => PropelTypes::BOOLEAN,
        'enabled' => PropelTypes::BOOLEAN_EMU,
        'updated_at' => PropelTypes::TIMESTAMP,

        'updated_at' => PropelTypes::TIMESTAMP,
        'updated_at' => PropelTypes::TIMESTAMP,
        'updated_at' => PropelTypes::TIMESTAMP,
    ];

    /**
     * @return $this
     */
    public function getTableMap(): self
    {
        // Allows to define methods in this class
        // to avoid a lot of mock classes
        return $this;
    }

    /**
     * @return ColumnMap[]
     */
    public function getPrimaryKeys(): array
    {
        $cm = new ColumnMap('id', new TableMap());
        $cm->setType('INTEGER');

        return ['id' => $cm];
    }

    /**
     * Method from the TableMap API
     * @param mixed $column
     * @return Column|null
     */
    public function getColumn($column): ?Column
    {
        if ($this->hasColumn($column)) {
            return new Column($column, $this->map[$column]);
        }

        return null;
    }

    /**
     * Method from the TableMap API
     * @param mixed $column
     * @return bool
     */
    public function hasColumn($column): bool
    {
        return in_array($column, array_keys($this->map));
    }

    /**
     * Method from the TableMap API
     * @return array
     */
    public function getRelations(): array
    {
        // table maps
        $authorTable = new TableMap();
        $authorTable->setClassName('\Foo\Author');

        $resellerTable = new TableMap();
        $resellerTable->setClassName('\Foo\Reseller');

        $defaultLocalTable = new TableMap('local');
        $defaultForeignTable = new TableMap('foreign');

        // relations
        $mainAuthorRelation = new RelationMap('MainAuthor', $defaultLocalTable, $authorTable);
        $mainAuthorRelation->setType(RelationMap::MANY_TO_ONE);

        $authorRelation = new RelationMap('Author', $defaultLocalTable, $authorTable);
        $authorRelation->setType(RelationMap::ONE_TO_MANY);

        $resellerRelation = new RelationMap('Reseller', $resellerTable, $defaultForeignTable);
        $resellerRelation->setType(RelationMap::MANY_TO_MANY);

        return [
            $mainAuthorRelation,
            $authorRelation,
            $resellerRelation
        ];
    }
}
