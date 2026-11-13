<?php

namespace Propel\Bundle\PropelBundle\Tests\Command;

use PDO;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Propel\Bundle\PropelBundle\Command\DatabaseCreateCommand;
use Propel\Bundle\PropelBundle\Command\DatabaseDropCommand;
use Propel\Bundle\PropelBundle\Service\DatabaseTableManager;
use Propel\Bundle\PropelBundle\Tests\SchemaBuilder;
use Propel\Bundle\PropelBundle\Util\DatabaseDsn;
use Propel\Runtime\Propel;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

/**
 * # DatabaseAdministrationTest
 */
class DatabaseAdministrationTest extends TestCase
{
    /**
     * @return void
     * @throws Throwable
     */
    public function testTablesWithForeignKeysCanBeDroppedOnTheSelectedDatabase(): void
    {
        $driver = strtok(getenv('TEST_DSN') ?: 'sqlite:', ':');
        if (!in_array($driver, ['sqlsrv', 'dblib', 'mssql', 'oci', 'oracle'], strict: true)) {
            self::markTestSkipped('Requires SQL Server or Oracle.');
        }
        $connection = SchemaBuilder::buildSchema(<<<'XML'
<database name="table_administration" namespace="Propel\Bundle\PropelBundle\Tests\Fixtures\Administration">
    <table name="admin_parent" phpName="AdminParent">
        <column name="id" type="INTEGER" primaryKey="true" />
    </table>
    <table name="admin_child" phpName="AdminChild">
        <column name="id" type="INTEGER" primaryKey="true" />
        <column name="parent_id" type="INTEGER" />
        <foreign-key foreignTable="admin_parent">
            <reference local="parent_id" foreign="id" />
        </foreign-key>
    </table>
</database>
XML
        );
        $manager = new DatabaseTableManager($connection, Propel::getAdapter('table_administration'));
        $names = [];
        foreach (Propel::getDatabaseMap('table_administration')->getTables() as $table) {
            $names[] = in_array($driver, ['oci', 'oracle'], strict: true) ? strtoupper($table->getName()) : $table->getName();
        }
        self::assertCount(2, array_intersect($names, $manager->getTables()));

        $manager->dropTables($names);

        self::assertSame([], array_intersect($names, $manager->getTables()));
    }

    /**
     * @return void
     * @throws Exception
     * @throws RandomException
     */
    public function testSqlServerCreatesAndDropsADatabaseUsingMaster(): void
    {
        $dsn = getenv('TEST_DSN') ?: '';
        $driver = strtok($dsn, ':');
        if (!in_array($driver, ['sqlsrv', 'dblib', 'mssql'], strict: true)) {
            self::markTestSkipped('Requires SQL Server with database administration privileges.');
        }
        $name = 'bundle-admin-' . bin2hex(random_bytes(4));
        $adminDsn = DatabaseDsn::forAdministration($dsn);
        $key = $driver === 'sqlsrv' ? 'Database' : 'dbname';
        $targetDsn = str_replace("$key=master", "$key=$name", $adminDsn);
        $admin = new PDO($adminDsn, getenv('TEST_USER') ?: null, getenv('TEST_PASSWORD') ?: null);
        $container = new ContainerBuilder();
        $container->setParameter('propel.configuration', ['database' => ['connections' => ['admin_test' => [
            'adapter' => $driver === 'sqlsrv' ? 'sqlsrv' : 'mssql', 'dsn' => $targetDsn,
            'user' => getenv('TEST_USER') ?: null, 'password' => getenv('TEST_PASSWORD') ?: null,
        ]]]]);
        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('getCacheDir')->willReturn(sys_get_temp_dir());
        $kernel->method('getEnvironment')->willReturn('test');
        try {
            $create = new DatabaseCreateCommand($container);
            $create->setApplication(new Application($kernel));
            self::assertSame(0, (new CommandTester($create))->execute([]));
            self::assertNotNull($admin->query("SELECT DB_ID('$name')")->fetchColumn());

            $drop = new DatabaseDropCommand($container);
            $drop->setApplication(new Application($kernel));
            self::assertSame(0, (new CommandTester($drop))->execute(['--force' => true]));
            self::assertNull($admin->query("SELECT DB_ID('$name')")->fetchColumn());
        } finally {
            $admin->exec('DROP DATABASE IF EXISTS ' . DatabaseDsn::quoteDatabaseName($name, 'sqlsrv'));
        }
    }
}
