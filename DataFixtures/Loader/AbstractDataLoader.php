<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\DataFixtures\Loader;

use Exception;
use InvalidArgumentException;
use Propel\Bundle\PropelBundle\DataFixtures\AbstractDataHandler;
use Propel\Bundle\PropelBundle\Util\PropelInflector;
use Propel\Generator\Model\PropelTypes;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Exception\LogicException;
use Propel\Runtime\Map\Exception\TableNotFoundException;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Propel;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

/**
 * # AbstractDataLoader
 * Abstract class to manage a common logic to load datas.
 *
 * @author William Durand <william.durand1@gmail.com>
 */
abstract class AbstractDataLoader extends AbstractDataHandler implements DataLoaderInterface
{
    /**
     * @var string[]
     */
    protected array $deletedClasses = [];

    /**
     * @var array<string, ActiveRecordInterface>
     */
    protected array $object_references = [];

    /**
     * @param string[] $files
     * @param string $connectionName
     * @return int
     * @throws ReflectionException
     * @throws Exception
     * @throws RuntimeException
     */
    #[\Override]
    public function load(array $files, string $connectionName): int
    {
        $nbFiles = 0;
        $this->deletedClasses = [];

        $this->loadMapBuilders($connectionName);
        $this->con = Propel::getConnection($connectionName);

        try {
            $this->getConnection()->beginTransaction();

            $datas = [];
            foreach ($files as $file) {
                $content = $this->transformDataToArray($file);

                if (count($content) > 0) {
                    $datas = array_merge_recursive($datas, $content);
                    $nbFiles++;
                }
            }

            $this->deleteCurrentData($datas);
            $this->loadDataFromArray($datas);

            $this->getConnection()->commit();
        } catch (Exception $e) {
            $this->getConnection()->rollBack();
            throw $e;
        }

        return $nbFiles;
    }

    /**
     * Transforms a file containing data in an array.
     *
     * @param string $file A filename.
     *
     * @return array<string, mixed>
     */
    abstract protected function transformDataToArray(string $file): array;

    /**
     * Deletes current data.
     *
     * @param array<string, mixed>|null $data The data to delete
     *
     * @return void
     * @throws ReflectionException
     */
    protected function deleteCurrentData(?array $data = null): void
    {
        if ($data !== null) {
            $classes = array_keys($data);
            foreach (array_reverse($classes) as $class) {
                $class = trim($class);
                if (in_array($class, $this->deletedClasses)) {
                    continue;
                }
                $this->deleteClassData($class);
            }
        }
    }

    /**
     * Delete data for a given class, and for its ancestors (if any).
     *
     * @param string $class Class name to delete
     *
     * @return void
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @throws TableNotFoundException
     * @throws RuntimeException
     */
    protected function deleteClassData(string $class): void
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Unknown class \"$class\".");
        }
        $tableMap = $this->getDatabaseMap()->getTable(constant(constant("$class::TABLE_MAP") . '::TABLE_NAME'));
        $tableMap->doDeleteAll($this->con);

        $this->deletedClasses[] = $class;

        // Remove ancestors data
        $baseClass = get_parent_class($class);
        if ($baseClass !== false && ($parentClass = get_parent_class($baseClass)) !== false) {
            $reflectionClass = new ReflectionClass($parentClass);
            if (!$reflectionClass->isAbstract()) {
                $this->deleteClassData($parentClass);
            }
        }
    }

    /**
     * Loads the data using the generated data model.
     *
     * @param array<string, mixed>|null $data The data to be loaded
     *
     * @return void
     * @throws ReflectionException
     */
    protected function loadDataFromArray(?array $data = null): void
    {
        if ($data === null) {
            return;
        }

        foreach ($data as $class => $datas) {
            // iterate through datas for this class
            // might have been empty just for force a table to be emptied on import
            if (!is_array($datas)) {
                continue;
            }

            $class = trim($class);
            if ($class[0] == '\\') {
                $class = substr($class, 1);
            }
            $tableMap = $this->getDatabaseMap()->getTable(constant(constant("$class::TABLE_MAP") . '::TABLE_NAME'));
            $column_names = $tableMap->getFieldnames(TableMap::TYPE_PHPNAME);

            foreach ($datas as $key => $values) {
                // create a new entry in the database
                if (!class_exists($class)) {
                    throw new InvalidArgumentException("Unknown class \"$class\".");
                }

                $obj = new $class();

                if (!$obj instanceof ActiveRecordInterface) {
                    throw new RuntimeException(
                        "The class \"$class\" is not a Propel class. "
                        . "There is probably another class named \"$class\" somewhere."
                    );
                }

                if (!is_array($values)) {
                    throw new InvalidArgumentException(
                        "You must give a name for each fixture data entry (class $class)."
                    );
                }

                foreach ($values as $name => $value) {
                    if (is_array($value) && str_ends_with($name, 's')) {
                        try {
                            // many-to-many relationship
                            $this->loadManyToMany($obj, substr($name, 0, -1), $value);

                            continue;
                        } catch (TableNotFoundException $e) {
                            // Check whether this is actually an array stored in the object.
                            $missingTableMessage = 'Cannot fetch TableMap for undefined table: '
                                . substr($name, 0, -1);
                            if ($missingTableMessage === $e->getMessage()) {
                                if ($tableMap->getColumn($name)->getType() !== PropelTypes::PHP_ARRAY
                                    && $tableMap->getColumn($name)->getType() !== PropelTypes::OBJECT) {
                                    throw $e;
                                }
                            }
                        }
                    }

                    if ($tableMap->hasColumn($name)) {
                        $column = $tableMap->getColumn($name);
                    } elseif ($tableMap->hasColumnByPhpName($name)) {
                        $column = $tableMap->getColumnByPhpName($name);
                    } else {
                        $column = null;
                    }

                    // foreign key?
                    if ($column !== null) {
                        /*
                         * A column, which is a PrimaryKey (self referencing, e.g. versionable behavior),
                         * but which is not a ForeignKey (e.g. delegatable behavior on 1:1 relation).
                         */
                        if ($column->isPrimaryKey() && $value !== null && !$column->isForeignKey()) {
                            if (isset($this->object_references[$this->cleanObjectRef("{$class}_$value")])) {
                                $obj = $this->object_references[$this->cleanObjectRef("{$class}_$value")];

                                continue;
                            }
                        }

                        if ($column->isForeignKey() && $value !== null) {
                            $relatedTable = $this->getDatabaseMap()->getTable($column->getRelatedTableName());
                            if (isset($this->object_references[$this->cleanObjectRef(
                                "{$relatedTable->getClassname()}_$value"
                            )])) {
                                $value = $this
                                    ->object_references[$this->cleanObjectRef("{$relatedTable->getClassname()}_$value")]
                                    ->getByName($column->getRelatedName(), TableMap::TYPE_COLNAME);
                            } else {
                                $relatedClass = $this->cleanObjectRef($relatedTable->getClassNameOrFail());
                                if (isset($data[$relatedClass]) || isset($data["\\$relatedClass"])) {
                                    throw new InvalidArgumentException(
                                        "The object \"$value\" from class \"{$relatedTable->getClassname()}\" "
                                        . 'is not defined in your data file.'
                                    );
                                }
                            }
                        }
                    }

                    if (($pos = array_search($name, $column_names)) !== false) {
                        $obj->setByPosition($pos, $value);
                    } elseif (is_callable([$obj, $method = 'set' . ucfirst(PropelInflector::camelize($name) ?? '')])) {
                        $obj->$method($value);
                    } else {
                        throw new InvalidArgumentException("Column \"$name\" does not exist for class \"$class\".");
                    }
                }

                $obj->save($this->con);

                $this->saveParentReference($class, $key, $obj);
            }
        }
    }

    /**
     * Loads many to many objects.
     *
     * @param ActiveRecordInterface $obj A Propel object
     * @param string $middleTableName The middle table name
     * @param string[] $values An array of values
     * @return void
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws TableNotFoundException
     * @throws RuntimeException
     */
    protected function loadManyToMany(ActiveRecordInterface $obj, string $middleTableName, array $values): void
    {
        $middleTable = $this->getDatabaseMap()->getTable($middleTableName);
        $middleClass = $middleTable->getClassNameOrFail();
        if (!class_exists($middleClass)) {
            throw new InvalidArgumentException("Unknown class \"$middleClass\".");
        }
        $tableName = constant(constant(get_class($obj) . '::TABLE_MAP') . '::TABLE_NAME');

        foreach ($middleTable->getColumns() as $column) {
            if ($column->isForeignKey()) {
                if ($tableName !== $column->getRelatedTableName()) {
                    $relatedClass = $this->getDatabaseMap()->getTable($column->getRelatedTableName())->getClassname();
                    $relation = $column->getRelation();
                    if ($relation === null) {
                        throw new RuntimeException('The foreign key relation is not defined.');
                    }
                    $relatedSetter = "set{$relation->getName()}";
                } else {
                    $relation = $column->getRelation();
                    if ($relation === null) {
                        throw new RuntimeException('The foreign key relation is not defined.');
                    }
                    $setter = "set{$relation->getName()}";
                }
            }
        }

        if (!isset($relatedClass, $setter, $relatedSetter)) {
            $objectClass = get_class($obj);
            throw new InvalidArgumentException(
                "Unable to find the many-to-many relationship for object \"$objectClass\"."
            );
        }

        foreach ($values as $value) {
            if (!isset($this->object_references[$this->cleanObjectRef("{$relatedClass}_$value")])) {
                throw new InvalidArgumentException(
                    "The object \"$value\" from class \"$relatedClass\" is not defined in your data file."
                );
            }

            $middle = new $middleClass();
            $middle->$setter($obj);
            $middle->$relatedSetter($this->object_references[$this->cleanObjectRef("{$relatedClass}_$value")]);
            $middle->save($this->con);
        }
    }

    /**
     * @param string $ref
     * @return string
     */
    protected function cleanObjectRef(string $ref): string
    {
        return $ref[0] === '\\' ? substr($ref, 1) : $ref;
    }

    /**
     * Save a reference to the specified object (and its ancestors) before loading them.
     *
     * @param string $class Class name of passed object
     * @param string $key Key identifying specified object
     * @param ActiveRecordInterface $obj A Propel object
     *
     * @return void
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    protected function saveParentReference(string $class, string $key, ActiveRecordInterface $obj): void
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Unknown class \"$class\".");
        }
        if (!method_exists($obj, 'getPrimaryKey')) {
            return;
        }

        $this->object_references[$this->cleanObjectRef("{$class}_$key")] = $obj;

        // Get parent (schema ancestor) of parent (Propel base class) in case of inheritance
        $baseClass = get_parent_class($class);
        if ($baseClass !== false && ($parentClass = get_parent_class($baseClass)) !== false) {
            $reflectionClass = new ReflectionClass($parentClass);
            if (!$reflectionClass->isAbstract()) {
                $parentObj = new $parentClass();
                if (!$parentObj instanceof ActiveRecordInterface) {
                    throw new RuntimeException('The parent model must implement ActiveRecordInterface.');
                }
                $parentObj->fromArray($obj->toArray());
                $this->saveParentReference($parentClass, $key, $parentObj);
            }
        }
    }
}
