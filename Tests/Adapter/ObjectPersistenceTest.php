<?php

namespace Propel\Bundle\PropelBundle\Tests\Adapter;

use Exception;
use PHPUnit\Framework\TestCase;
use Propel\Bundle\PropelBundle\Tests\Fixtures\ObjectPersistence\BinaryObjectRecord;
use Propel\Bundle\PropelBundle\Tests\Fixtures\ObjectPersistence\BinaryObjectRecordQuery;
use Propel\Bundle\PropelBundle\Tests\Fixtures\ObjectPersistence\Map\BinaryObjectRecordTableMap;
use Propel\Bundle\PropelBundle\Tests\SchemaBuilder;

/**
 * # ObjectPersistenceTest
 */
class ObjectPersistenceTest extends TestCase
{
    /**
     * @return void
     * @throws Exception
     */
    public function testSerializedObjectsSurviveInsertUpdateAndReload(): void
    {
        $connection = SchemaBuilder::buildSchema(<<<'XML'
<database name="object_persistence" defaultIdMethod="native" namespace="Propel\Bundle\PropelBundle\Tests\Fixtures\ObjectPersistence">
    <table name="binary_object_record" phpName="BinaryObjectRecord">
        <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true" />
        <column name="payload" type="OBJECT" />
    </table>
</database>
XML
        );
        $first = (object)['value' => str_repeat("first\0\xc3\xa9", 16384)];
        $second = (object)['value' => "second\0\xc3\xa0"];
        $record = new BinaryObjectRecord();
        $record->setPayload($first)->save($connection);
        $id = $record->getId();
        self::assertNotNull($id);
        BinaryObjectRecordTableMap::clearInstancePool();

        $loaded = BinaryObjectRecordQuery::create()->findPk($id, $connection);
        self::assertNotNull($loaded);
        self::assertEquals($first, $loaded->getPayload());

        $loaded->setPayload($second)->save($connection);
        BinaryObjectRecordTableMap::clearInstancePool();
        $updated = BinaryObjectRecordQuery::create()->findPk($id, $connection);
        self::assertNotNull($updated);
        self::assertEquals($second, $updated->getPayload());

        $updated->setPayload(null)->save($connection);
        BinaryObjectRecordTableMap::clearInstancePool();
        $empty = BinaryObjectRecordQuery::create()->findPk($id, $connection);
        self::assertNotNull($empty);
        self::assertNull($empty->getPayload());
    }
}
