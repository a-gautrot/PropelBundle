<?php

namespace Propel\Bundle\PropelBundle\Adapter;

use Override;
use PDO;
use Propel\Generator\Model\PropelTypes;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Adapter\Exception\UnsupportedEncodingException;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\StatementInterface;
use Propel\Runtime\Map\ColumnMap;
use Propel\Runtime\Map\DatabaseMap;
use RuntimeException;

/**
 * # SqlsrvAdapter
 * Bind serialized OBJECT columns as binary values, matching Propel's VARBINARY(MAX) mapping.
 */
class SqlsrvAdapter extends MssqlAdapter
{
    /**
     * @param ConnectionInterface $con
     * @param array<string, mixed> $settings
     */
    #[Override]
    public function initConnection(ConnectionInterface $con, array $settings): void
    {
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $con->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);

        parent::initConnection($con, $settings);
    }

    /**
     * @param ConnectionInterface $con
     * @param string $charset
     * @return void
     */
    #[Override]
    public function setCharset(ConnectionInterface $con, string $charset): void
    {
        switch (strtolower($charset)) {
            case 'utf-8':
                $encoding = PDO::SQLSRV_ENCODING_UTF8;
                break;
            case 'system':
                $encoding = PDO::SQLSRV_ENCODING_SYSTEM;
                break;
            default:
                throw new UnsupportedEncodingException(
                    'Only utf-8 or system encoding is supported by the pdo_sqlsrv driver.'
                );
        }
        $con->setAttribute(PDO::SQLSRV_ATTR_ENCODING, $encoding);
    }

    /**
     * @param string $sql
     * @param list<array{table: ?string, column: string, value: mixed}> $params
     * @param Criteria $values
     * @param DatabaseMap $dbMap
     */
    #[Override]
    public function cleanupSQL(string &$sql, array &$params, Criteria $values, DatabaseMap $dbMap): void
    {
        // PDO_SQLSRV binds binary values directly; it must not inherit FreeTDS's hex literal conversion.
        $bindings = [];
        foreach ($params as $index => $param) {
            if ($param['table'] === null || $param['value'] !== null) {
                continue;
            }
            $column = $dbMap->getTable($param['table'])->getColumn($param['column']);
            if ($column->isLob() || $column->getType() === PropelTypes::OBJECT) {
                $parameter = ':p' . ($index + 1);
                $bindings[$parameter] = "CONVERT(VARBINARY(MAX), $parameter)";
            }
        }
        if ($bindings === []) {
            return;
        }
        $rewritten = preg_replace_callback(
            '/:p\d+\b/',
            static fn(array $match): string => $bindings[$match[0]] ?? $match[0],
            $sql
        );
        if ($rewritten === null) {
            throw new RuntimeException('Cannot prepare the SQLSRV binary statement.');
        }
        $sql = $rewritten;
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
        if (($cMap->getType() === PropelTypes::OBJECT && (is_resource($value) || is_string($value)))
            || ($cMap->isLob() && is_resource($value))) {
            if (is_resource($value)) {
                rewind($value);
            }
            return $stmt->bindParam($parameter, $value, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
        }

        return parent::bindValue($stmt, $parameter, $value, $cMap, $position);
    }
}
