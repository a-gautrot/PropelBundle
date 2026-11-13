<?php

namespace Propel\Bundle\PropelBundle\Tests\Util;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Propel\Bundle\PropelBundle\Util\DatabaseDsn;

/**
 * # DatabaseDsnTest
 */
class DatabaseDsnTest extends TestCase
{
    /**
     * @return iterable
     */
    public static function administrationDsns(): iterable
    {
        yield ['dblib:host=localhost:1433;dbname=bundle-test;charset=UTF-8', 'bundle-test',
            'dblib:host=localhost:1433;charset=UTF-8;dbname=master'];
        yield ['mssql:dbname=bundle-test;host=localhost', 'bundle-test', 'dblib:host=localhost;dbname=master'];
        yield ['sqlsrv:Server=localhost,1433;Database=bundle-test;TrustServerCertificate=1', 'bundle-test',
            'sqlsrv:Server=localhost,1433;TrustServerCertificate=1;Database=master'];
        yield ['sqlsrv:database=bundle test;Server=localhost', 'bundle test', 'sqlsrv:Server=localhost;Database=master'];
        yield ['mysql:dbname=bundle-test;host=localhost;charset=utf8mb4', 'bundle-test', 'mysql:host=localhost;charset=utf8mb4'];
        yield ['pgsql:host=localhost;dbname=bundle.test', 'bundle.test', 'pgsql:host=localhost'];
    }

    /**
     * @return iterable
     */
    public static function databaseNames(): iterable
    {
        yield ['bundle-test', 'sqlsrv', '[bundle-test]'];
        yield ['bundle]; DROP DATABASE other;--', 'sqlsrv', '[bundle]]; DROP DATABASE other;--]'];
        yield ['bundle`test', 'mysql', '`bundle``test`'];
        yield ['bundle"test', 'pgsql', '"bundle""test"'];
    }

    /**
     * @param string $dsn
     * @param string $name
     * @param string $expected
     * @return void
     */
    #[DataProvider(methodName: 'administrationDsns')]
    public function testAdministrationUsesTheServerWithoutTheTargetDatabase(string $dsn, string $name, string $expected): void
    {
        self::assertSame($name, DatabaseDsn::getDatabaseName($dsn));
        self::assertSame($expected, DatabaseDsn::forAdministration($dsn));
    }

    /**
     * @return void
     */
    public function testSqliteHasNoDatabaseName(): void
    {
        self::assertNull(DatabaseDsn::getDatabaseName('sqlite::memory:'));
    }

    /**
     * @param string $name
     * @param string $adapter
     * @param string $expected
     * @return void
     */
    #[DataProvider(methodName: 'databaseNames')]
    public function testNamesAreQuotedAsIdentifiers(string $name, string $adapter, string $expected): void
    {
        self::assertSame($expected, DatabaseDsn::quoteDatabaseName($name, $adapter));
    }
}
