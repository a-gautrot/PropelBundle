<?php

namespace Propel\Bundle\PropelBundle\Adapter;

use Override;
use Propel\Generator\Model\PropelTypes;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Adapter\Pdo\MssqlAdapter as BaseMssqlAdapter;
use Propel\Runtime\Map\DatabaseMap;
use RuntimeException;

/**
 * # MssqlAdapter
 * FreeTDS requires binary values as hexadecimal SQL literals, including serialized OBJECT columns.
 */
class MssqlAdapter extends BaseMssqlAdapter
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
        $bindings = [];
        $remaining = [];
        foreach ($params as $index => $param) {
            $column = $param['table'] === null
                ? null : $dbMap->getTable($param['table'])->getColumn($param['column']);
            $value = $param['value'];
            if ($column !== null && ($column->isLob() || $column->getType() === PropelTypes::OBJECT)
                && (is_resource($value) || is_string($value))) {
                if (is_resource($value)) {
                    rewind($value);
                    $value = stream_get_contents($value);
                    if ($value === false) {
                        throw new RuntimeException('Cannot read the MSSQL binary parameter.');
                    }
                }
                $bindings[':p' . ($index + 1)] = '0x' . bin2hex($value);
            } else {
                $remaining[] = $param;
                $bindings[':p' . ($index + 1)] = ':p' . count($remaining);
            }
        }
        $rewritten = preg_replace_callback(
            '/:p\d+\b/',
            static fn(array $match): string => $bindings[$match[0]] ?? $match[0],
            $sql
        );
        if ($rewritten === null) {
            throw new RuntimeException('Cannot prepare the MSSQL binary statement.');
        }
        $sql = $rewritten;
        $params = $remaining;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    #[Override]
    protected function prepareParams(array $params): array
    {
        if (isset($params['dsn']) && str_starts_with($params['dsn'], 'mssql:')) {
            $params['dsn'] = 'dblib:' . substr($params['dsn'], 6);
        }

        return parent::prepareParams($params);
    }
}
