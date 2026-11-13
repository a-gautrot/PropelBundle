<?php

namespace Propel\Bundle\PropelBundle\Tests\Adapter;

use PHPUnit\Framework\TestCase;
use Propel\Bundle\PropelBundle\Adapter\MssqlAdapter;
use Propel\Bundle\PropelBundle\Adapter\SqlsrvAdapter;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Map\DatabaseMap;

/**
 * # SqlsrvAdapterTest
 */
class SqlsrvAdapterTest extends TestCase
{
    /**
     * @return void
     */
    public function testSqlsrvInheritsMssqlWithoutApplyingFreeTdsBinaryConversion(): void
    {
        $adapter = new SqlsrvAdapter();
        self::assertInstanceOf(MssqlAdapter::class, $adapter);
        $map = new DatabaseMap('sqlsrv_test');
        $table = $map->addTable('test_data');
        $table->addColumn('payload', 'Payload', 'OBJECT', false);
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, 'serialized object');
        $params = [['table' => 'test_data', 'column' => 'payload', 'value' => $stream]];
        $sql = 'INSERT INTO test_data (payload) VALUES (:p1)';
        try {
            $adapter->cleanupSQL($sql, $params, new Criteria('sqlsrv_test'), $map);

            self::assertSame('INSERT INTO test_data (payload) VALUES (:p1)', $sql);
            self::assertSame($stream, $params[0]['value']);
        } finally {
            fclose($stream);
        }
    }

    /**
     * @return void
     */
    public function testNullBinaryValuesAreConvertedWithoutChangingOtherPlaceholderNames(): void
    {
        $adapter = new SqlsrvAdapter();
        $map = new DatabaseMap('sqlsrv_test');
        $table = $map->addTable('test_data');
        $table->addColumn('payload', 'Payload', 'OBJECT', false);
        $table->addColumn('data', 'Data', 'BLOB', false);
        $params = [];
        for ($index = 1; $index <= 10; $index++) {
            $params[] = ['table' => 'test_data', 'column' => $index === 1 ? 'data' : 'payload',
                'value' => $index <= 2 ? null : 'binary value'];
        }
        $original = $params;
        $sql = 'UPDATE test_data SET data = :p1, payload = :p2 WHERE id = :p10';

        $adapter->cleanupSQL($sql, $params, new Criteria('sqlsrv_test'), $map);

        self::assertSame(
            'UPDATE test_data SET data = CONVERT(VARBINARY(MAX), :p1), payload = CONVERT(VARBINARY(MAX), :p2) WHERE id = :p10',
            $sql
        );
        self::assertSame($original, $params);
    }
}
