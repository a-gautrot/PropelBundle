<?php

namespace Propel\Bundle\PropelBundle\Tests\Service;

use PDO;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Propel\Bundle\PropelBundle\Service\DatabaseTableManager;
use Propel\Runtime\Adapter\Pdo\MysqlAdapter;
use Propel\Runtime\Adapter\Pdo\OracleAdapter;
use Propel\Runtime\Adapter\Pdo\SqlsrvAdapter;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\StatementInterface;
use RuntimeException;
use Throwable;

/**
 * # DatabaseTableManagerTest
 */
class DatabaseTableManagerTest extends TestCase
{
    /**
     * @return void
     * @throws Exception
     * @throws Throwable
     */
    public function testMysqlRestoresForeignKeyChecksAfterFailure(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $executed = [];
        $connection->expects(self::exactly(3))->method('exec')->willReturnCallback(static function (string $sql) use (&$executed): int {
            $executed[] = $sql;
            if (str_starts_with($sql, 'DROP TABLE')) {
                throw new RuntimeException('drop failed');
            }
            return 0;
        });
        try {
            (new DatabaseTableManager($connection, new MysqlAdapter()))->dropTables(['book']);
            self::fail('Expected the database error.');
        } catch (RuntimeException $exception) {
            self::assertSame('drop failed', $exception->getMessage());
        }
        self::assertSame('SET FOREIGN_KEY_CHECKS = 1', end($executed));
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testSqlServerRefusesToModifyTablesOutsideTheSelection(): void
    {
        $connection = $this->connectionWithForeignKey('outside');
        $connection->expects(self::never())->method('exec');
        $connection->expects(self::never())->method('beginTransaction');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('outside the selection');
        (new DatabaseTableManager($connection, new SqlsrvAdapter()))->dropTables(['book']);
    }

    /**
     * @param string $table
     * @return ConnectionInterface
     * @throws Exception
     */
    private function connectionWithForeignKey(string $table): ConnectionInterface
    {
        $statement = $this->createMock(StatementInterface::class);
        $statement->expects(self::once())->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn([
            ['table_name' => $table, 'constraint_name' => 'fk_book', 'referenced_table' => 'book', 'in_schema' => 1],
        ]);
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('query')->willReturn($statement);

        return $connection;
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testSqlServerRollsBackConstraintRemovalWhenDroppingFails(): void
    {
        $connection = $this->connectionWithForeignKey('author');
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())->method('rollBack');
        $connection->expects(self::never())->method('commit');
        $executed = [];
        $connection->method('exec')->willReturnCallback(static function (string $sql) use (&$executed): int {
            $executed[] = $sql;
            if (str_starts_with($sql, 'DROP TABLE')) {
                throw new RuntimeException('drop failed');
            }
            return 0;
        });
        try {
            (new DatabaseTableManager($connection, new SqlsrvAdapter()))->dropTables(['book', 'author']);
            self::fail('Expected the database error.');
        } catch (RuntimeException $exception) {
            self::assertSame('drop failed', $exception->getMessage());
        }
        self::assertSame('ALTER TABLE [author] DROP CONSTRAINT [fk_book]', $executed[0]);
    }

    /**
     * @return void
     * @throws Exception
     * @throws Throwable
     */
    public function testOracleQuotesNamesAndDropsDependentConstraints(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())->method('exec')->with('DROP TABLE "Book""s" CASCADE CONSTRAINTS')->willReturn(0);
        (new DatabaseTableManager($connection, new OracleAdapter()))->dropTables(['Book"s']);
    }
}
