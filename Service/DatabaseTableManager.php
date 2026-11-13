<?php

namespace Propel\Bundle\PropelBundle\Service;

use Closure;
use PDO;
use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Adapter\Pdo\MssqlAdapter;
use Propel\Runtime\Adapter\Pdo\MysqlAdapter;
use Propel\Runtime\Adapter\Pdo\OracleAdapter;
use Propel\Runtime\Connection\ConnectionInterface;
use RuntimeException;
use Throwable;

/**
 * # DatabaseTableManager
 * Table administration in the current schema (SQL Server/Oracle) or database (MySQL).
 */
final class DatabaseTableManager
{
    /**
     * @var ConnectionInterface
     * @readonly
     */
    private ConnectionInterface $connection;
    /**
     * @var AdapterInterface
     * @readonly
     */
    private AdapterInterface $adapter;
    /**
     * @param ConnectionInterface $connection
     * @param AdapterInterface $adapter
     */
    public function __construct(ConnectionInterface $connection, AdapterInterface    $adapter)
    {
        $this->connection = $connection;
        $this->adapter = $adapter;
    }

    /**
     * @return list<string>
     */
    public function getTables(): array
    {
        switch (true) {
            case $this->adapter instanceof MysqlAdapter:
                $sql = 'SHOW TABLES';
                break;
            case $this->adapter instanceof MssqlAdapter:
                $sql = 'SELECT name FROM sys.tables WHERE schema_id = SCHEMA_ID() AND is_ms_shipped = 0';
                break;
            case $this->adapter instanceof OracleAdapter:
                $sql = "SELECT table_name FROM user_tables WHERE dropped = 'NO'";
                break;
            default:
                throw new RuntimeException('Table administration supports MySQL, SQL Server and Oracle only.');
        }
        $statement = $this->connection->query($sql);
        if ($statement === false) {
            throw new RuntimeException('Cannot list the database tables.');
        }

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * @param list<string> $tables
     * @throws Throwable
     */
    public function dropTables(array $tables): void
    {
        if ($tables === []) {
            return;
        }
        if ($this->adapter instanceof MysqlAdapter) {
            $this->connection->exec('SET FOREIGN_KEY_CHECKS = 0');
            try {
                $names = implode(', ', array_map(Closure::fromCallable([$this, 'quoteIdentifier']), $tables));
                $this->connection->exec("DROP TABLE $names");
            } finally {
                $this->connection->exec('SET FOREIGN_KEY_CHECKS = 1');
            }
        } elseif ($this->adapter instanceof OracleAdapter) {
            foreach ($tables as $table) {
                $this->connection->exec('DROP TABLE ' . $this->quoteIdentifier($table) . ' CASCADE CONSTRAINTS');
            }
        } elseif ($this->adapter instanceof MssqlAdapter) {
            $this->dropSqlServerTables($tables);
        } else {
            throw new RuntimeException('Table administration supports MySQL, SQL Server and Oracle only.');
        }
    }

    /**
     * @param string $name
     * @return string
     */
    private function quoteIdentifier(string $name): string
    {
        switch (true) {
            case $this->adapter instanceof MysqlAdapter:
                return '`' . str_replace('`', '``', $name) . '`';
            case $this->adapter instanceof MssqlAdapter:
                return '[' . str_replace(']', ']]', $name) . ']';
            default:
                return '"' . str_replace('"', '""', $name) . '"';
        }
    }

    /**
     * @param list<string> $tables
     * @throws Throwable
     */
    private function dropSqlServerTables(array $tables): void
    {
        $statement = $this->connection->query(
            'SELECT parent.name AS table_name, fk.name AS constraint_name, referenced.name AS referenced_table, '
            . 'CASE WHEN parent.schema_id = SCHEMA_ID() THEN 1 ELSE 0 END AS in_schema '
            . 'FROM sys.foreign_keys fk '
            . 'JOIN sys.tables parent ON parent.object_id = fk.parent_object_id '
            . 'JOIN sys.tables referenced ON referenced.object_id = fk.referenced_object_id '
            . 'WHERE referenced.schema_id = SCHEMA_ID()'
        );
        if ($statement === false) {
            throw new RuntimeException('Cannot list foreign keys.');
        }
        $constraints = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (!in_array($row['referenced_table'], $tables, true)) {
                continue;
            }
            if (!$row['in_schema'] || !in_array($row['table_name'], $tables, true)) {
                throw new RuntimeException('Cannot drop a table referenced by a table outside the selection.');
            }
            $constraints[] = 'ALTER TABLE ' . $this->quoteIdentifier($row['table_name'])
                . ' DROP CONSTRAINT ' . $this->quoteIdentifier($row['constraint_name']);
        }

        $this->connection->beginTransaction();
        try {
            foreach ($constraints as $sql) {
                $this->connection->exec($sql);
            }
            foreach ($tables as $table) {
                $this->connection->exec('DROP TABLE ' . $this->quoteIdentifier($table));
            }
            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }
}
