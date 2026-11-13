<?php

namespace Propel\Bundle\PropelBundle\Tests\Generator\Builder;

use PHPUnit\Framework\TestCase;
use Propel\Bundle\PropelBundle\Generator\Builder\ObjectBuilder;
use Propel\Bundle\PropelBundle\Generator\Platform\OraclePlatform;
use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Generator\Util\QuickBuilder;

/**
 * # ObjectBuilderTest
 */
class ObjectBuilderTest extends TestCase
{
    /**
     * @return void
     */
    public function testOracleObjectInsertGeneratesSequenceIdBeforeUsingCriteria(): void
    {
        $builder = new QuickBuilder();
        $builder->setConfig(new QuickGeneratorConfig(['propel' => ['generator' => [
            'objectModel' => ['builders' => ['object' => ObjectBuilder::class]],
        ]]]));
        $builder->setPlatform(new OraclePlatform());
        $builder->setSchema(<<<'XML'
<database name="oracle_object_builder" defaultIdMethod="native">
    <table name="object_record">
        <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true" />
        <column name="payload" type="OBJECT" />
    </table>
</database>
XML
        );

        $code = $builder->getClasses(['object']);

        self::assertStringContainsString('SELECT object_record_SEQ.nextval FROM dual', $code);
        self::assertStringContainsString('$this->id = (int) $dataFetcher->fetchColumn();', $code);
        self::assertStringContainsString('$criteria = $this->buildCriteria();', $code);
        self::assertStringContainsString('$pk = $criteria->doInsert($con);', $code);
    }
}
