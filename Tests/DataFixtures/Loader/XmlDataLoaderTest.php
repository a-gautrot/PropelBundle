<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Tests\DataFixtures\Loader;

use Exception;
use Propel\Bundle\PropelBundle\DataFixtures\Loader\XmlDataLoader;
use Propel\Bundle\PropelBundle\Tests\DataFixtures\TestCase;
use Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBookQuery;

/**
 * # XmlDataLoaderTest
 * @author William Durand <william.durand1@gmail.com>
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class XmlDataLoaderTest extends TestCase
{
    public function testXmlLoadPreservesDefaultNamespacesAndIgnoresPrefixedMetadata(): void
    {
        $fixtures = <<<'XML'
<Fixtures xmlns="urn:fixtures" xmlns:metadata="urn:metadata">
    <metadata:IgnoredModel><metadata:ignored_record /></metadata:IgnoredModel>
    <CoolBookAuthor Namespace="Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader">
        <CoolBookAuthor_2 id="2" name="Namespaced author" metadata:note="ignored" />
    </CoolBookAuthor>
    <CoolBook Namespace="Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader">
        <metadata:ignored_record />
        <CoolBook_2 id="2" name="Namespaced book" author_id="CoolBookAuthor_2" metadata:note="ignored" />
    </CoolBook>
</Fixtures>
XML;

        $loader = new XmlDataLoader(__DIR__ . '/../../Fixtures/DataFixtures/Loader', []);
        $loader->load([$this->getTempFile($fixtures)], 'default');

        $books = CoolBookQuery::create()->find($this->con);
        $this->assertCount(1, $books);
        $this->assertSame('Namespaced book', $books[0]->getName());
        $this->assertSame('Namespaced author', $books[0]->getCoolBookAuthor()->getName());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testXmlLoad(): void
    {
        $fixtures = <<<XML
<Fixtures>
    <CoolBookAuthor Namespace="Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader">
        <CoolBookAuthor_1 id="1" name="A famous one" />
    </CoolBookAuthor>
    <CoolBook Namespace="Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader">
        <CoolBook_1 id="1" name="An important one" author_id="CoolBookAuthor_1" />
    </CoolBook>
</Fixtures>
XML;

        $filename = $this->getTempFile($fixtures);

        $loader = new XmlDataLoader(__DIR__ . '/../../Fixtures/DataFixtures/Loader', []);
        $loader->load([$filename], 'default');

        $books = CoolBookQuery::create()->find($this->con);
        $this->assertCount(1, $books);

        $book = $books[0];
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBookAuthor', $book->getCoolBookAuthor());
    }
}
