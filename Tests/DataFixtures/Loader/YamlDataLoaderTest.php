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
use Faker\Factory;
use Propel\Bundle\PropelBundle\DataFixtures\Loader\YamlDataLoader;
use Propel\Bundle\PropelBundle\Tests\DataFixtures\TestCase;
use Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBookAuthorQuery;
use Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBookQuery;
use Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlBookWithObjectQuery;
use Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlDelegateOnPrimaryKeyAuthorQuery;
use Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlInheritedRelationshipBookQuery;
use Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyAuthorQuery;
use Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyBookAuthorQuery;
use Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyBookQuery;
use Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesAuthorQuery;
use Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesBookAuthorQuery;
use Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesBookQuery;
use Propel\Bundle\PropelBundle\Tests\SchemaBuilder;

/**
 * # YamlDataLoaderTest
 * @author William Durand <william.durand1@gmail.com>
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class YamlDataLoaderTest extends TestCase
{
    /**
     * @return void
     * @throws Exception
     */
    public function testYamlLoadOneToMany(): void
    {
        $fixtures = <<<YAML
\Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBookAuthor:
    CoolBookAuthor_1:
        id: '1'
        name: 'A famous one'
\Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBook:
    CoolBook_1:
        id: '1'
        name: 'An important one'
        author_id: CoolBookAuthor_1

YAML;
        $filename = $this->getTempFile($fixtures);

        $loader = new YamlDataLoader(__DIR__ . '/../../Fixtures/DataFixtures/Loader', []);
        $loader->load([$filename], 'default');

        $books = CoolBookQuery::create()->find($this->con);
        $this->assertCount(1, $books);

        $book = $books[0];
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBookAuthor', $book->getCoolBookAuthor());
    }

    /**
     * @throws Exception
     */
    public function testYamlLoadOneToManyExternalReference(): void
    {
        $loader = new YamlDataLoader(__DIR__ . '/../../Fixtures/DataFixtures/Loader', []);

        $fixtures = <<<YAML
\Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBookAuthor:
    CoolBookAuthor_1:
        id: '1'
        name: 'A famous one'

YAML;
        $filename = $this->getTempFile($fixtures);
        $loader->load([$filename], 'default');

        $fixtures = <<<YAML
\Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBook:
    CoolBook_1:
        id: '1'
        name: 'An important one'
        author_id: 1

YAML;
        $filename = $this->getTempFile($fixtures);
        $loader->load([$filename], 'default');

        $books = CoolBookQuery::create()->find($this->con);
        $this->assertCount(1, $books);

        $book = $books[0];
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBookAuthor', $book->getCoolBookAuthor());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testLoadSelfReferencing()
    {
        $fixtures = <<<YAML
Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBookAuthor:
    CoolBookAuthor_1:
        id: '1'
        name: 'to be announced'
    CoolBookAuthor_2:
        id: CoolBookAuthor_1
        name: 'A famous one'

YAML;
        $filename = $this->getTempFile($fixtures);

        $loader = new YamlDataLoader(__DIR__ . '/../../Fixtures/DataFixtures/Loader', []);
        $loader->load([$filename], 'default');

        $books = CoolBookQuery::create()->find($this->con);
        $this->assertCount(0, $books);

        $authors = CoolBookAuthorQuery::create()->find($this->con);
        $this->assertCount(1, $authors);

        $author = $authors[0];
        $this->assertEquals('A famous one', $author->getName());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testLoaderWithPhp(): void
    {
        $fixtures = <<<YAML
Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBookAuthor:
    CoolBookAuthor_1:
        id: '1'
        name: <?php echo "to be announced"; ?>

YAML;
        $filename = $this->getTempFile($fixtures);

        $loader = new YamlDataLoader(__DIR__ . '/../../Fixtures/DataFixtures/Loader', []);
        $loader->load([$filename], 'default');

        $books = CoolBookQuery::create()->find($this->con);
        $this->assertCount(0, $books);

        $authors = CoolBookAuthorQuery::create()->find($this->con);
        $this->assertCount(1, $authors);

        $author = $authors[0];
        $this->assertEquals('to be announced', $author->getName());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testLoadWithoutFaker(): void
    {
        $fixtures = <<<YAML
Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBookAuthor:
    CoolBookAuthor_1:
        id: '1'
        name: <?php echo \$faker('word'); ?>

YAML;
        $filename = $this->getTempFile($fixtures);

        $loader = new YamlDataLoader(__DIR__ . '/../../Fixtures/DataFixtures/Loader', []);
        $loader->load([$filename], 'default');

        $books = CoolBookQuery::create()->find($this->con);
        $this->assertCount(0, $books);

        $authors = CoolBookAuthorQuery::create()->find($this->con);
        $this->assertCount(1, $authors);

        $author = $authors[0];
        $this->assertEquals('word', $author->getName());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testLoadWithFaker(): void
    {
        if (!class_exists('Faker\Factory')) {
            $this->markTestSkipped('Faker is mandatory');
        }

        $fixtures = <<<YAML
Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBook:
    CoolBook_1:
        id: '1'
        name: <?php \$faker('word'); ?>
        description: <?php \$faker('sentence'); ?>

YAML;
        $filename = $this->getTempFile($fixtures);

        $loader = new YamlDataLoader(__DIR__ . '/../../Fixtures/DataFixtures/Loader', [], Factory::create());
        $loader->load([$filename], 'default');

        $books = CoolBookQuery::create()->find($this->con);
        $this->assertCount(1, $books);

        $book = $books[0];
        $this->assertNotNull($book->getName());
        $this->assertNotEquals('null', strtolower($book->getName()));
        $this->assertMatchesRegularExpression('#[a-z]+#', $book->getName());
        $this->assertNotNull($book->getDescription());
        $this->assertNotEquals('null', strtolower($book->getDescription()));
        $this->assertMatchesRegularExpression('#[\w ]+#', $book->getDescription());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testYamlLoadManyToMany(): void
    {
        $schema = <<<XML
<database name="default" package="vendor.bundles.Propel.Bundle.PropelBundle.Tests.Fixtures.DataFixtures.Loader" namespace="Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader" defaultIdMethod="native">
    <table name="table_book" phpName="YamlManyToManyBook">
        <column name="id" type="integer" primaryKey="true" />
        <column name="name" type="varchar" size="255" />
    </table>

    <table name="table_author" phpName="YamlManyToManyAuthor">
        <column name="id" type="integer" primaryKey="true" />
        <column name="name" type="varchar" size="255" />
    </table>

    <table name="table_book_author" phpName="YamlManyToManyBookAuthor" isCrossRef="true">
        <column name="book_id" type="integer" required="true" primaryKey="true" />
        <column name="author_id" type="integer" required="true" primaryKey="true" />

        <foreign-key foreignTable="table_book" phpName="Book" onDelete="CASCADE" onUpdate="CASCADE">
            <reference local="book_id" foreign="id" />
        </foreign-key>
        <foreign-key foreignTable="table_author" phpName="Author" onDelete="CASCADE" onUpdate="CASCADE">
            <reference local="author_id" foreign="id" />
        </foreign-key>
    </table>
</database>
XML;

        $fixtures = <<<YAML
\Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyBook:
    Book_1:
        id: 1
        name: 'An important one'
    Book_2:
        id: 2
        name: 'Les misérables'

\Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyAuthor:
    Author_1:
        id: 1
        name: 'A famous one'
    Author_2:
        id: 2
        name: 'Victor Hugo'
        table_book_authors: [ Book_2 ]

\Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyBookAuthor:
    BookAuthor_1:
        book_id: Book_1
        author_id: Author_1
YAML;

        $con = SchemaBuilder::buildSchema($schema);
        $crossTable = YamlManyToManyBookAuthorQuery::create()->getTableMap()->getName();
        $fixtures = str_replace('table_book_authors:', $crossTable . 's:', $fixtures);
        $filename = $this->getTempFile($fixtures);

        $loader = new YamlDataLoader(__DIR__ . '/../../Fixtures/DataFixtures/Loader', []);
        $loader->load([$filename], 'default');

        $books = YamlManyToManyBookQuery::create()->find($con);
        $this->assertCount(2, $books);
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyBook', $books[0]);
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyBook', $books[1]);

        $authors = YamlManyToManyAuthorQuery::create()->find($con);
        $this->assertCount(2, $authors);
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyAuthor', $authors[0]);
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyAuthor', $authors[1]);

        $bookAuthors = YamlManyToManyBookAuthorQuery::create()->find($con);
        $this->assertCount(2, $bookAuthors);
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyBookAuthor', $bookAuthors[0]);
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyBookAuthor', $bookAuthors[1]);

        $this->assertEquals('Victor Hugo', $authors[1]->getName());
        $this->assertTrue($authors[1]->getBooks()->contains($books[1]));
        $this->assertEquals('Les misérables', $authors[1]->getBooks()->get(0)->getName());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testYamlLoadManyToManyMultipleFiles(): void
    {
        $schema = <<<XML
<database name="default" package="vendor.bundles.Propel.Bundle.PropelBundle.Tests.Fixtures.DataFixtures.Loader" namespace="Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader" defaultIdMethod="native">
    <table name="table_book_multiple" phpName="YamlManyToManyMultipleFilesBook">
        <column name="id" type="integer" primaryKey="true" />
        <column name="name" type="varchar" size="255" />
    </table>

    <table name="table_author_multiple" phpName="YamlManyToManyMultipleFilesAuthor">
        <column name="id" type="integer" primaryKey="true" />
        <column name="name" type="varchar" size="255" />
    </table>

    <table name="table_book_author_multiple" phpName="YamlManyToManyMultipleFilesBookAuthor" isCrossRef="true">
        <column name="book_id" type="integer" required="true" primaryKey="true" />
        <column name="author_id" type="integer" required="true" primaryKey="true" />

        <foreign-key foreignTable="table_book_multiple" phpName="Book" onDelete="CASCADE" onUpdate="CASCADE">
            <reference local="book_id" foreign="id" />
        </foreign-key>
        <foreign-key foreignTable="table_author_multiple" phpName="Author" onDelete="CASCADE" onUpdate="CASCADE">
            <reference local="author_id" foreign="id" />
        </foreign-key>
    </table>
</database>
XML;

        $fixtures1 = <<<YAML
Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesBook:
    Book_2:
        id: 2
        name: 'Les misérables'

Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesAuthor:
    Author_1:
        id: 1
        name: 'A famous one'

Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesBookAuthor:
    BookAuthor_1:
        book_id: Book_1
        author_id: Author_1
YAML;

        $fixtures2 = <<<YAML
Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesBook:
    Book_1:
        id: 1
        name: 'An important one'

Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesAuthor:
    Author_2:
        id: 2
        name: 'Victor Hugo'
        table_book_author_multiples: [ Book_2 ]
YAML;

        $con = SchemaBuilder::buildSchema($schema);
        $crossTable = YamlManyToManyMultipleFilesBookAuthorQuery::create()->getTableMap()->getName();
        $fixtures2 = str_replace('table_book_author_multiples:', $crossTable . 's:', $fixtures2);
        $filename1 = $this->getTempFile($fixtures1);
        $filename2 = $this->getTempFile($fixtures2);

        $loader = new YamlDataLoader(__DIR__ . '/../../Fixtures/DataFixtures/Loader', []);
        $loader->load([$filename1, $filename2], 'default');

        $books = YamlManyToManyMultipleFilesBookQuery::create()->orderById()->find($con);
        $this->assertCount(2, $books);
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesBook', $books[0]);
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesBook', $books[1]);

        $authors = YamlManyToManyMultipleFilesAuthorQuery::create()->orderById()->find($con);
        $this->assertCount(2, $authors);
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesAuthor', $authors[0]);
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesAuthor', $authors[1]);

        $bookAuthors = YamlManyToManyMultipleFilesBookAuthorQuery::create()->find($con);
        $this->assertCount(2, $bookAuthors);
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesBookAuthor', $bookAuthors[0]);
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlManyToManyMultipleFilesBookAuthor', $bookAuthors[1]);

        $this->assertEquals('Victor Hugo', $authors[1]->getName());
        $this->assertTrue($authors[1]->getBooks()->contains($books[1]));
        $this->assertEquals('Les misérables', $authors[1]->getBooks()->get(0)->getName());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testLoadWithInheritedRelationship(): void
    {
        $schema = <<<XML
<database name="default" package="vendor.bundles.Propel.Bundle.PropelBundle.Tests.Fixtures.DataFixtures.Loader" namespace="Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader" defaultIdMethod="native">

    <table name="table_book_inherited_relationship" phpName="YamlInheritedRelationshipBook">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="name" type="varchar" size="255" />
        <column name="author_id" type="integer" required="true" />
        <foreign-key foreignTable="table_author_inherited_relationship" phpName="Author">
            <reference local="author_id" foreign="id" />
        </foreign-key>
    </table>

    <table name="table_author_inherited_relationship" phpName="YamlInheritedRelationshipAuthor">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="name" type="varchar" size="255" />
        <column name="descendant_class" type="varchar" size="255" />
    </table>

    <table name="table_nobelized_author_inherited_relationship" phpName="YamlInheritedRelationshipNobelizedAuthor">
        <column name="nobel_year" type="integer" />
        <behavior name="concrete_inheritance">
            <parameter name="extends" value="table_author_inherited_relationship" />
        </behavior>
    </table>

</database>
XML;

        $fixtures = <<<YAML
Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlInheritedRelationshipNobelizedAuthor:
    NobelizedAuthor_1:
        nobel_year: 2012

Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlInheritedRelationshipBook:
    Book_1:
        name: 'Supplice du santal'
        author_id: NobelizedAuthor_1
YAML;

        $filename = $this->getTempFile($fixtures);

        $con = SchemaBuilder::buildSchema($schema);

        $loader = new YamlDataLoader(__DIR__ . '/../../Fixtures/DataFixtures/Loader', []);
        $loader->load([$filename], 'default');

        $books = YamlInheritedRelationshipBookQuery::create()->find($con);
        $this->assertCount(1, $books);

        $book = $books[0];
        $author = $book->getAuthor();
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlInheritedRelationshipAuthor', $author);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testLoadArrayToObjectType(): void
    {
        $schema = <<<XML
<database name="default" package="vendor.bundles.Propel.Bundle.PropelBundle.Tests.Fixtures.DataFixtures.Loader" namespace="Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader" defaultIdMethod="native">
    <table name="table_book_with_object" phpName="YamlBookWithObject">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="name" type="varchar" size="255" />
        <column name="options" type="object" />
    </table>
</database>
XML;
        $fixtures = <<<YAML
Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlBookWithObject:
    book1:
        name: my book
        options: {opt1: 2012, opt2: 140, inner: {subOpt: 123}}
YAML;

        $filename = $this->getTempFile($fixtures);

        $con = SchemaBuilder::buildSchema($schema);

        $loader = new YamlDataLoader(__DIR__ . '/../../Fixtures/DataFixtures/Loader', []);
        $loader->load([$filename], 'default');

        $book = YamlBookWithObjectQuery::create(null)->findOne($con);

        $this->assertInstanceOf('\Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlBookWithObject', $book);
        $this->assertEquals(['opt1' => 2012, 'opt2' => 140, 'inner' => ['subOpt' => 123]], $book->getOptions());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testLoadDelegatedOnPrimaryKey(): void
    {
        $schema = <<<XML
<database name="default" package="vendor.bundles.Propel.Bundle.PropelBundle.Tests.Fixtures.DataFixtures.Loader" namespace="Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader" defaultIdMethod="native">
    <table name="yaml_delegate_on_primary_key_person" phpName="YamlDelegateOnPrimaryKeyPerson">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="name" type="varchar" size="255" />
    </table>

    <table name="yaml_delegate_on_primary_key_author" phpName="YamlDelegateOnPrimaryKeyAuthor">
        <column name="id" type="integer" primaryKey="true" autoIncrement="false" />
        <column name="count_books" type="integer" defaultValue="0" required="true" />

        <behavior name="delegate">
            <parameter name="to" value="yaml_delegate_on_primary_key_person" />
        </behavior>

        <foreign-key foreignTable="yaml_delegate_on_primary_key_person" onDelete="RESTRICT" onUpdate="CASCADE">
            <reference local="id" foreign="id" />
        </foreign-key>
    </table>
</database>
XML;

        $fixtures = <<<YAML
Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlDelegateOnPrimaryKeyPerson:
    yaml_delegate_on_primary_key_person_1:
        name: "Some Persons Name"

Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlDelegateOnPrimaryKeyAuthor:
    yaml_delegate_on_primary_key_author_1:
        id: yaml_delegate_on_primary_key_person_1
        count_books: 7
YAML;

        $filename = $this->getTempFile($fixtures);

        $con = SchemaBuilder::buildSchema($schema);

        $loader = new YamlDataLoader(__DIR__ . '/../../Fixtures/DataFixtures/Loader', []);
        $loader->load([$filename], 'default');

        $authors = YamlDelegateOnPrimaryKeyAuthorQuery::create()->find($con);
        $this->assertCount(1, $authors);

        $author = $authors[0];
        $person = $author->getYamlDelegateOnPrimaryKeyPerson();
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\YamlDelegateOnPrimaryKeyPerson', $person);
    }
}
