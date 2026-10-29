<?php

namespace Propel\Bundle\PropelBundle\Tests\Form\ChoiceList;

use Exception;
use PHPUnit\Framework\TestCase;
use Propel\Bundle\PropelBundle\Form\ChoiceList\PropelChoiceLoader;
use Propel\Bundle\PropelBundle\Tests\Fixtures\ChoiceList\ChoiceLoaderItem;
use Propel\Bundle\PropelBundle\Tests\Fixtures\ChoiceList\ChoiceLoaderItemQuery;
use Propel\Bundle\PropelBundle\Tests\SchemaBuilder;
use stdClass;
use Symfony\Component\Form\ChoiceList\Factory\DefaultChoiceListFactory;

/**
 * # PropelChoiceLoaderTest
 */
class PropelChoiceLoaderTest extends TestCase
{
    /**
     * @var ChoiceLoaderItem[]
     */
    private static array $items = [];

    /**
     * @return void
     * @throws Exception
     */
    public static function setUpBeforeClass(): void
    {
        $schema = <<<'XML'
<database name="choice_loader" namespace="Propel\Bundle\PropelBundle\Tests\Fixtures\ChoiceList">
    <table name="choice_loader_item" phpName="ChoiceLoaderItem">
        <column name="id" type="INTEGER" primaryKey="true" />
        <column name="code" type="VARCHAR" size="32" />
        <column name="enabled" type="BOOLEAN" />
    </table>
</database>
XML;
        SchemaBuilder::buildSchema($schema);
        foreach ([1 => 'one', 2 => 'two', 3 => 'three'] as $id => $code) {
            $item = new ChoiceLoaderItem();
            $item->setId($id)->setCode($code)->setEnabled($id !== 2)->save();
            self::$items[$id] = $item;
        }
    }

    /**
     * @return void
     */
    public function testSubmittedValuesKeepTheirKeysAndOrderAndRespectTheQuery(): void
    {
        $query = ChoiceLoaderItemQuery::create()->filterByEnabled(true);
        $loader = new PropelChoiceLoader(new DefaultChoiceListFactory(), ChoiceLoaderItem::class, $query);

        $choices = $loader->loadChoicesForValues(['last' => '3', 'excluded' => '2', 'missing' => '99', 'first' => '1']);

        $this->assertSame(['last' => 3, 'first' => 1], array_map(static fn($item) => $item->getId(), $choices));
        $this->assertSame(2, $query->count());
    }

    /**
     * @return void
     */
    public function testChoiceValuesKeepTheirKeysAndIgnoreOtherClasses(): void
    {
        $loader = new PropelChoiceLoader(new DefaultChoiceListFactory(), ChoiceLoaderItem::class, ChoiceLoaderItemQuery::create());

        $this->assertSame(['last' => '3', 'first' => '1'], $loader->loadValuesForChoices([
            'last' => self::$items[3],
            'invalid' => new stdClass(),
            'first' => self::$items[1],
        ]));
        $this->assertSame([], $loader->loadChoicesForValues([]));
        $this->assertSame([], $loader->loadValuesForChoices([]));
    }

    /**
     * @return void
     */
    public function testCustomIdentifierUsesItsColumnForBothDirections(): void
    {
        $loader = new PropelChoiceLoader(new DefaultChoiceListFactory(), ChoiceLoaderItem::class, ChoiceLoaderItemQuery::create(), 'code');

        $choices = $loader->loadChoicesForValues(['third' => 'three', 'first' => 'one']);

        $this->assertSame(['third' => 3, 'first' => 1], array_map(static fn($item) => $item->getId(), $choices));
        $this->assertSame(['third' => 'three', 'first' => 'one'], $loader->loadValuesForChoices($choices));
    }

    /**
     * @return void
     */
    public function testLoadedChoiceListIsCachedAndSupportsPartialLookups(): void
    {
        $loader = new PropelChoiceLoader(new DefaultChoiceListFactory(), ChoiceLoaderItem::class, ChoiceLoaderItemQuery::create()->orderById());
        $value = static fn($item) => (string)$item->getId();
        $list = $loader->loadChoiceList($value);

        $this->assertSame($list, $loader->loadChoiceList($value));
        $this->assertSame(['1', '2', '3'], $list->getValues());
        $this->assertSame(['second' => self::$items[2]], $loader->loadChoicesForValues(['second' => '2'], $value));
    }
}
