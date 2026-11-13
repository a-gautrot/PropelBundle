<?php

namespace Propel\Bundle\PropelBundle\Adapter;

use Override;
use Propel\Generator\Model\PropelTypes;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Adapter\Pdo\OracleAdapter as BaseOracleAdapter;
use Propel\Runtime\Connection\StatementInterface;
use Propel\Runtime\Map\ColumnMap;
use Propel\Runtime\Map\DatabaseMap;
use RuntimeException;

/**
 * # OracleAdapter
 * Write streamed OBJECT values through persistent Oracle BLOB locators.
 */
class OracleAdapter extends BaseOracleAdapter
{
    /**
     * @param string $sql
     * @param list<array{table: ?string, column: string, value: mixed}> $params
     * @param Criteria $values
     * @param DatabaseMap $dbMap
     */
    #[Override]
    public function cleanupSQL(string &$sql, array &$params, Criteria $values, DatabaseMap $dbMap): void
    {
        if (!preg_match('/^\s*(INSERT|UPDATE)\b/i', $sql)) {
            return;
        }
        $assignments = preg_split('/\bWHERE\b/i', $sql, 2)[0];
        $columns = [];
        $bindings = [];
        foreach ($params as $index => $param) {
            if ($param['table'] === null || $param['value'] === null) {
                continue;
            }
            $column = $dbMap->getTable($param['table'])->getColumn($param['column']);
            $parameter = ':p' . ($index + 1);
            $pattern = '/' . preg_quote($parameter, '/') . '\b/';
            if ($column->getType() !== PropelTypes::OBJECT || !preg_match($pattern, $assignments)) {
                continue;
            }
            $rewritten = preg_replace($pattern, 'EMPTY_BLOB()', $sql, 1);
            if ($rewritten === null) {
                throw new RuntimeException('Cannot prepare the Oracle BLOB statement.');
            }
            $sql = $rewritten;
            $columns[] = $column->getTable()->isIdentifierQuotingEnabled()
                ? $this->quoteIdentifier($column->getName()) : $column->getName();
            $bindings[] = $parameter;
        }
        if ($columns !== []) {
            $sql .= ' RETURNING ' . implode(', ', $columns) . ' INTO ' . implode(', ', $bindings);
        }
    }

    /**
     * @param StatementInterface $stmt
     * @param string $parameter
     * @param mixed $value
     * @param ColumnMap $cMap
     * @param int|null $position
     * @return bool
     */
    #[Override]
    public function bindValue(
        StatementInterface $stmt,
        string             $parameter,
        $value,
        ColumnMap          $cMap,
        ?int               $position = null
    ): bool {
        if ($cMap->getType() === PropelTypes::OBJECT && is_resource($value)) {
            rewind($value);
        }

        return parent::bindValue($stmt, $parameter, $value, $cMap, $position);
    }
}
