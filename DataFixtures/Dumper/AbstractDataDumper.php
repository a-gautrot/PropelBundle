<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\DataFixtures\Dumper;

use PDO;
use Propel\Bundle\PropelBundle\DataFixtures\AbstractDataHandler;
use Propel\Generator\Model\PropelTypes;
use Propel\Runtime\Map\ColumnMap;
use Propel\Runtime\Propel;
use RuntimeException;
use ValueError;

/**
 * # AbstractDataDumper
 * Abstract class to manage a common logic to dump data.
 *
 * @author William Durand <william.durand1@gmail.com>
 */
abstract class AbstractDataDumper extends AbstractDataHandler implements DataDumperInterface
{
    /**
     * @param string|null $filename
     * @param string|null $connectionName
     * @return void
     */
    public function dump(?string $filename, ?string $connectionName = null): void
    {
        if ($filename === null || $filename === '') {
            throw new RuntimeException('Invalid filename provided.');
        }

        $this->loadMapBuilders($connectionName);
        $this->con = Propel::getConnection($connectionName);

        $array = $this->getDataAsArray();
        $data = $this->transformArrayToData($array);

        if (file_put_contents($filename, $data) === false) {
            throw new RuntimeException("Cannot write file: $filename");
        }
    }

    /**
     * Dumps data to fixture from a given connection and
     * returns an array.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    protected function getDataAsArray(): array
    {
        $tables = [];
        foreach ($this->dbMap->getTables() as $table) {
            $tables[] = $table->getClassname();
        }

        $tables = $this->fixOrderingOfForeignKeyData($tables);

        $dumpData = [];
        foreach ($tables as $tableName) {
            $tableMap = $this->dbMap->getTable(constant(constant("$tableName::TABLE_MAP") . '::TABLE_NAME'));
            $hasParent = false;
            $haveParents = false;
            $fixColumn = null;

            $shortTableName = substr($tableName, strrpos($tableName, '\\') + 1, strlen($tableName));

            foreach ($tableMap->getColumns() as $column) {
                $col = strtolower($column->getName());
                if ($column->isForeignKey()) {
                    $relatedTable = $this->dbMap->getTable($column->getRelatedTableName());
                    if ($tableName === $relatedTable->getPhpName()) {
                        if ($hasParent) {
                            $haveParents = true;
                        } else {
                            $fixColumn = $column;
                            $hasParent = true;
                        }
                    }
                }
            }

            if ($haveParents) {
                // unable to dump tables having multi-recursive references
                continue;
            }

            // get db info
            $resultsSets = [];
            if ($hasParent) {
                $resultsSets[] = $this->fixOrderingOfForeignKeyDataInSameTable($resultsSets, $tableName, $fixColumn);
            } else {
                $in = [];
                $adapter = Propel::getAdapter($this->dbMap->getName());
                foreach ($tableMap->getColumns() as $column) {
                    $in[] = $adapter->quoteIdentifier(strtolower($column->getName()));
                }
                $columns = implode(', ', $in);
                $stmt = $this
                    ->con
                    ->query("SELECT $columns FROM {$adapter->quoteIdentifier($tableMap->getName())}");

                $set = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $set[] = $row;
                }
                $resultsSets[] = $set;
                $stmt->close();
                unset($stmt);
            }

            foreach ($resultsSets as $rows) {
                if (count($rows) > 0 && !isset($dumpData[$tableName])) {
                    $dumpData[$tableName] = [];

                    foreach ($rows as $row) {
                        $pk = $shortTableName;
                        $values = [];
                        $primaryKeys = [];
                        $foreignKeys = [];

                        foreach ($tableMap->getColumns() as $column) {
                            $col = strtolower($column->getName());
                            $isPrimaryKey = $column->isPrimaryKey();

                            if ($row[$col] === null) {
                                continue;
                            }

                            if ($isPrimaryKey) {
                                $value = $row[$col];
                                $pk .= "_$value";
                                $primaryKeys[$col] = $value;
                            }

                            if ($column->isForeignKey()) {
                                $relatedTable = $this->dbMap->getTable($column->getRelatedTableName());
                                if ($isPrimaryKey) {
                                    $foreignKeys[$col] = $row[$col];
                                    $primaryKeys[$col] = "{$relatedTable->getPhpName()}_$row[$col]";
                                } else {
                                    $values[$col] = "{$relatedTable->getPhpName()}_$row[$col]";
                                    $values[$col] = strlen($row[$col]) ? "{$relatedTable->getPhpName()}_$row[$col]" : '';
                                }
                            } elseif (!$isPrimaryKey || !$tableMap->isUseIdGenerator()) {
                                if (!empty($row[$col]) && $column->getType() === PropelTypes::PHP_ARRAY) {
                                    $serialized = substr($row[$col], 2, -2);
                                    $row[$col] = $serialized ? explode(' | ', $serialized) : [];
                                }

                                // We did not want auto incremented primary keys
                                $values[$col] = $row[$col];
                            }

                            if ($column->getType() === PropelTypes::OBJECT) {
                                $values[$col] = unserialize($row[$col]);
                            }
                        }

                        if (count($primaryKeys) > 1 || (!empty($primaryKeys) && !empty($foreignKeys))) {
                            $values = array_merge($primaryKeys, $values);
                        }

                        $dumpData[$tableName][$pk] = $values;
                    }
                }
            }
        }

        return $dumpData;
    }

    /**
     * Fixes the ordering of foreign key data, by outputting data
     * a foreign key depends on before the table with the foreign key.
     *
     * @param string[] $classes The array with the class names
     *
     * @return string[]
     */
    protected function fixOrderingOfForeignKeyData(array $classes): array
    {
        // reordering classes to take foreign keys into account
        for ($i = 0, $count = count($classes); $i < $count; $i++) {
            $class = $classes[$i];
            $tableMap = $this->dbMap->getTable(constant(constant("$class::TABLE_MAP") . '::TABLE_NAME'));

            foreach ($tableMap->getColumns() as $column) {
                if ($column->isForeignKey()) {
                    $relatedTable = $this->dbMap->getTable($column->getRelatedTableName());
                    $relatedTablePos = array_search($relatedTable->getClassname(), $classes);

                    // check if relatedTable is after the current table
                    if ($relatedTablePos > $i) {
                        // move related table 1 position before current table
                        $classes = array_merge(
                            array_slice($classes, 0, $i),
                            [$classes[$relatedTablePos]],
                            array_slice($classes, $i, $relatedTablePos - $i),
                            array_slice($classes, $relatedTablePos + 1)
                        );
                        // we have moved a table, so let's see if we are done
                        return $this->fixOrderingOfForeignKeyData($classes);
                    }
                }
            }
        }

        return $classes;
    }

    /**
     * @param array<int, array<string, mixed>> $resultsSets
     * @param string $tableName
     * @param ColumnMap $column
     * @param string|null $in
     *
     * @return array<int, array<string, mixed>>
     */
    protected function fixOrderingOfForeignKeyDataInSameTable(array $resultsSets, string $tableName, ColumnMap $column, ?string $in = null): array
    {
        // Avoid SQL injection via string-based query concatenation for $tableName, $columnName, and $in
        if (!preg_match('/^\w+$/', $tableName)) {
            throw new ValueError("Invalid table name: $tableName");
        }

        $columnName = strtolower($column->getName());

        if (!preg_match('/^\w+$/', $columnName)) {
            throw new ValueError("Invalid column name: $columnName");
        }

        if ($in !== null) {
            $values = explode(',', $in);

            foreach ($values as $value) {
                if (!preg_match('/^\w+$/', trim($value, " '"))) {
                    throw new ValueError("Invalid \"IN\" value for SQL: $in");
                }
            }
        }

        $databaseTableName = constant(constant("$tableName::TABLE_MAP") . '::TABLE_NAME');
        $condition = $in === null ? 'IS NULL' : "IN ($in)";
        $sql = "SELECT * FROM $databaseTableName WHERE $columnName $condition";

        $stmt = $this->con->prepare($sql);
        $stmt->execute();

        $in = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $in[] = "'{$row[strtolower($column->getRelatedColumnName())]}'";
            $resultsSets[] = $row;
        }

        if ($in = implode(', ', $in)) {
            $resultsSets = $this->fixOrderingOfForeignKeyDataInSameTable($resultsSets, $tableName, $column, $in);
        }

        return $resultsSets;
    }

    /**
     * Transforms an array of data to a specific format
     * depending on the specialized dumper. It should return
     * a string content ready to write in a file.
     *
     * @param array<string, array<string, array<string, mixed>>> $data
     *
     * @return string
     */
    abstract protected function transformArrayToData(array $data): string;
}
