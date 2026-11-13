<?php

namespace Propel\Bundle\PropelBundle\Tests\Adapter;

use PHPUnit\Framework\TestCase;
use Propel\Bundle\PropelBundle\Adapter\MssqlAdapter;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Map\DatabaseMap;

/**
 * # MssqlAdapterTest
 */
class MssqlAdapterTest extends TestCase
{
    /**
     * @return void
     */
    public function testBinaryParametersPreserveOtherBindingsAndReusableStreams(): void
    {
        $map = new DatabaseMap('mssql_test');
        $table = $map->addTable('test_data');
        $table->addColumn('payload', 'Payload', 'OBJECT', false);
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "serialized\0'\xc3\xa9");
        $original = [];
        $placeholders = [];
        for ($index = 1; $index <= 12; $index++) {
            $original[] = in_array($index, [1, 10], true)
                ? ['table' => 'test_data', 'column' => 'payload', 'value' => $stream]
                : ['table' => null, 'column' => 'scalar', 'value' => $index];
            $placeholders[] = ':p' . $index;
        }
        $template = 'INSERT INTO test_data VALUES (' . implode(', ', $placeholders) . ')';
        $adapter = new MssqlAdapter();
        try {
            for ($iteration = 0; $iteration < 2; $iteration++) {
                $sql = $template;
                $params = $original;
                $adapter->cleanupSQL($sql, $params, new Criteria('mssql_test'), $map);

                self::assertCount(10, $params);
                self::assertSame([2, 3, 4, 5, 6, 7, 8, 9, 11, 12], array_column($params, 'value'));
                $hex = '0x' . bin2hex("serialized\0'\xc3\xa9");
                self::assertSame(
                    "INSERT INTO test_data VALUES ($hex, :p1, :p2, :p3, :p4, :p5, :p6, :p7, :p8, $hex, :p9, :p10)",
                    $sql
                );
                self::assertIsResource($stream);
            }
        } finally {
            fclose($stream);
        }
    }
}
