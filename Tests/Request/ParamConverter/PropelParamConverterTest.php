<?php

namespace Propel\Bundle\PropelBundle\Tests\Request\ParamConverter;

use Exception;
use LogicException;
use Propel\Bundle\PropelBundle\Request\ParamConverter\PropelParamConverter;
use Propel\Bundle\PropelBundle\Tests\Fixtures\Model\Book;
use Propel\Bundle\PropelBundle\Tests\Fixtures\Model\Map\BookTableMap;
use Propel\Bundle\PropelBundle\Tests\SchemaBuilder;
use Propel\Bundle\PropelBundle\Tests\TestCase;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionWrapper;
use Propel\Runtime\Propel;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * # PropelParamConverterTest
 */
class PropelParamConverterTest extends TestCase
{
    /**
     * @var ConnectionWrapper|null $con
     */
    protected ?ConnectionWrapper $con = null;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        // @fixme: some tests fail if instance pooling is disabled...
        //Propel::disableInstancePooling();

        $this->loadDatabaseMap([BookTableMap::class]);
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        //Propel::enableInstancePooling();

        if ($this->con) {
            $this->con->useDebug(value: false);
        }
    }

    /**
     * @return void
     */
    public function testParamConverterSupport(): void
    {
        $paramConverter = new PropelParamConverter();

        $argument = new ArgumentMetadata('book', Book::class, isVariadic: false, hasDefaultValue: false, defaultValue: null);
        $this->assertTrue($paramConverter->supports($argument), 'param converter should support propel class');

        $argument = new ArgumentMetadata('fakeClass', 'fakeClass', isVariadic: false, hasDefaultValue: false, defaultValue: null);
        $this->assertFalse($paramConverter->supports($argument), 'param converter should not support wrong class');

        $argument = new ArgumentMetadata('test', TestCase::class, isVariadic: false, hasDefaultValue: false, defaultValue: null);
        $this->assertFalse($paramConverter->supports($argument), 'param converter should not support wrong class');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testParamConverterFindPk(): void
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request([], [], ['id' => 1, 'book' => null]);
        $argument = new ArgumentMetadata('book', Book::class, isVariadic: false, hasDefaultValue: false, defaultValue: null);

        $paramConverter->resolve($request, $argument);

        $this->assertInstanceOf(
            Book::class, $request->attributes->get('book'),
            'param "book" should be an instance of "Propel\Bundle\PropelBundle\Tests\Fixtures\Model\Book"'
        );
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testParamConverterFindPkNotFound(): void
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request([], [], ['id' => 2, 'book' => null]);
        $argument = new ArgumentMetadata('book', Book::class, isVariadic: false, hasDefaultValue: false, defaultValue: null);

        $this->expectException(NotFoundHttpException::class);

        $paramConverter->resolve($request, $argument);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testParamConverterFindSlug(): void
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request([], [], ['slug' => 'my-book', 'book' => null]);
        $argument = new ArgumentMetadata('book', Book::class, isVariadic: false, hasDefaultValue: false, defaultValue: null);
        $paramConverter->resolve($request, $argument);
        $this->assertInstanceOf(Book::class, $request->attributes->get('book'),
            'param "book" should be an instance of "Propel\Bundle\PropelBundle\Tests\Fixtures\Model\Book"');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testParamConverterFindCamelCasedSlug(): void
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request([], [], ['author_slug' => 'my-author', 'slug' => 'my-kewl-book', 'book' => null]);
        $argument = new ArgumentMetadata('book', Book::class, isVariadic: false, hasDefaultValue: false, defaultValue: null);

        $paramConverter->resolve($request, $argument);
        $this->assertInstanceOf(Book::class, $request->attributes->get('book'),
            'param "book" should be an instance of "Propel\Bundle\PropelBundle\Tests\Fixtures\Model\Book"');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testParamConverterFindSlugNotFound(): void
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request([], [], ['slug' => 'my-foo', 'book' => null]);
        $argument = new ArgumentMetadata('book', Book::class, isVariadic: false, hasDefaultValue: false, defaultValue: null);

        $this->expectException(NotFoundHttpException::class);

        $paramConverter->resolve($request, $argument);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testParamConverterFindBySlugNotByName(): void
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request([], [], ['slug' => 'my-book', 'name' => 'foo', 'book' => null]);
        $request->attributes->set('propel_converter', ['book' => ['exclude' => ['name']]]);
        $argument = new ArgumentMetadata('book', Book::class, isVariadic: false, hasDefaultValue: false, defaultValue: null, isNullable: false);
        $paramConverter->resolve($request, $argument);
        $this->assertInstanceOf(Book::class, $request->attributes->get('book'),
            'param "book" should be an instance of "Propel\Bundle\PropelBundle\Tests\Fixtures\Model\Book"');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testParamConverterFindByAllParamExcluded(): void
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request([], [], ['slug' => 'my-book', 'name' => 'foo', 'book' => null]);
        $request->attributes->set('propel_converter', ['book' => ['exclude' => ['name', 'slug']]]);
        $argument = new ArgumentMetadata('book', Book::class, isVariadic: false, hasDefaultValue: false, defaultValue: null, isNullable: false);

        $this->expectException(LogicException::class);

        $paramConverter->resolve($request, $argument);

        $this->assertInstanceOf(Book::class, $request->attributes->get('book'),
            'param "book" should be an instance of "Propel\Bundle\PropelBundle\Tests\Fixtures\Model\Book"');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testParamConverterFindByIdExcluded(): void
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request([], [], ['id' => '1234', 'book' => null]);
        $request->attributes->set('propel_converter', ['book' => ['exclude' => ['id']]]);
        $argument = new ArgumentMetadata('book', Book::class, isVariadic: false, hasDefaultValue: false, defaultValue: null, isNullable: false);

        $this->expectException(LogicException::class);

        $paramConverter->resolve($request, $argument);

        $this->assertInstanceOf(Book::class, $request->attributes->get('book'),
            'param "book" should be an instance of "Propel\Bundle\PropelBundle\Tests\Fixtures\Model\Book"');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testParamConverterFindLogicError(): void
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request([], [], ['book' => null]);
        $argument = new ArgumentMetadata('book', Book::class, isVariadic: false, hasDefaultValue: false, defaultValue: null);

        $this->expectException(LogicException::class);

        $paramConverter->resolve($request, $argument);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testParamConverterFindWithOptionalParam(): void
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request([], [], ['book' => null]);
        $argument = new ArgumentMetadata('book', Book::class, isVariadic: false, hasDefaultValue: false, defaultValue: null, isNullable: true);
        $paramConverter->resolve($request, $argument);

        $this->assertNull($request->attributes->get('book'),
            'param "book" should be null if book is not found and the parameter is optional');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testParamConverterFindWithMapping(): void
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request([], [], ['toto' => 1, 'book' => null]);
        $request->attributes->set('propel_converter', ['book' => ['mapping' => ['toto' => 'id']]]);
        $argument = new ArgumentMetadata('book', Book::class, isVariadic: false, hasDefaultValue: false, defaultValue: null, isNullable: false);
        $paramConverter->resolve($request, $argument);
        $this->assertInstanceOf(Book::class, $request->attributes->get('book'),
            'param "book" should be an instance of "Propel\Bundle\PropelBundle\Tests\Fixtures\Model\Book"');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testParamConverterFindSlugWithMapping(): void
    {
        $paramConverter = new PropelParamConverter();
        $request = new Request([], [], ['slugParam_special' => 'my-book', 'book' => null]);
        $request->attributes->set('propel_converter', ['book' => ['mapping' => ['slugParam_special' => 'slug']]]);
        $argument = new ArgumentMetadata('book', Book::class, isVariadic: false, hasDefaultValue: false, defaultValue: null, isNullable: false);
        $paramConverter->resolve($request, $argument);
        $this->assertInstanceOf(Book::class, $request->attributes->get('book'),
            'param "book" should be an instance of "Propel\Bundle\PropelBundle\Tests\Fixtures\Model\Book"');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testParamConvertWithOptionWith(): void
    {
        $this->loadFixtures();

        $paramConverter = new PropelParamConverter();
        $request = new Request([], [], ['id' => 1, 'book' => null]);
        $request->attributes->set('propel_converter', ['book' => ['with' => 'MyAuthor']]);
        $argument = new ArgumentMetadata('book', 'Propel\Bundle\PropelBundle\Tests\Request\ParamConverter\MyBook', isVariadic: false, hasDefaultValue: false, defaultValue: null, isNullable: false);

        $nb = $this->con->getQueryCount();
        $paramConverter->resolve($request, $argument);

        $book = $request->attributes->get('book');
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Request\ParamConverter\MyBook', $book,
            'param "book" should be an instance of "Propel\Bundle\PropelBundle\Tests\Request\ParamConverter\MyBook"');

        $this->assertEquals($nb + 1, $this->con->getQueryCount(), 'only one query to get the book');

        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Request\ParamConverter\MyAuthor', $book->getMyAuthor(),
            'param "book" should be an instance of "Propel\Bundle\PropelBundle\Tests\Request\ParamConverter\MyAuthor"');

        $this->assertEquals($nb + 1, $this->con->getQueryCount(), 'no new query to get the author');
        Propel::enableInstancePooling();
    }

    /**
     * @return void
     */
    protected function loadFixtures(): void
    {
        $schema = <<<XML
<database name="default" package="vendor.bundles.Propel.Bundle.PropelBundle.Tests.Request.ParamConverter"
    namespace="Propel\Bundle\PropelBundle\Tests\Request\ParamConverter" defaultIdMethod="native">
    <table name="my_book">
        <column name="id" type="integer" primaryKey="true" />
        <column name="name" type="varchar" size="255" />
        <column name="my_author_id" type="integer" required="true" />

        <foreign-key foreignTable="my_author" onDelete="CASCADE" onUpdate="CASCADE">
            <reference local="my_author_id" foreign="id" />
        </foreign-key>
    </table>

    <table name="my_author">
        <column name="id" type="integer" primaryKey="true" />
        <column name="name" type="varchar" size="255" />
    </table>
</database>
XML;

        if (!class_exists('Propel\Bundle\PropelBundle\Tests\Request\ParamConverter\MyAuthor')) {
            SchemaBuilder::buildSchema($schema);
        }

        $this->con = Propel::getConnection('default');
        $this->con->useDebug();

        MyBookQuery::create()->deleteAll($this->con);
        MyAuthorQuery::create()->deleteAll($this->con);

        $author = new MyAuthor();
        $author->setId(10);
        $author->setName('Will');

        $book = new MyBook();
        $book->setId(1);
        $book->setName('PropelBook');
        $book->setMyAuthor($author);

        $book2 = new MyBook();
        $book2->setId(2);
        $book2->setName('sf2lBook');
        $book2->setMyAuthor($author);

        $author->save($this->con);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testParamConvertWithOptionWithLeftJoin(): void
    {
        $this->loadFixtures();

        $paramConverter = new PropelParamConverter();
        $request = new Request([], [], ['param1' => 10, 'author' => null]);
        $request->attributes->set('propel_converter', ['author' => [
            'with' => [['MyBook', 'left join']],
            'mapping' => ['param1' => 'id'],
        ]]);
        $argument = new ArgumentMetadata('author', 'Propel\Bundle\PropelBundle\Tests\Request\ParamConverter\MyAuthor', isVariadic: false, hasDefaultValue: false, defaultValue: null, isNullable: false);

        $nb = $this->con->getQueryCount();
        $paramConverter->resolve($request, $argument);

        $author = $request->attributes->get('author');
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Request\ParamConverter\MyAuthor', $author,
            'param "author" should be an instance of "Propel\Bundle\PropelBundle\Tests\Request\ParamConverter\MyAuthor"');

        $this->assertEquals($nb + 1, $this->con->getQueryCount(), 'only one query to get the author');

        $books = $author->getMyBooks();
        $this->assertInstanceOf(ObjectCollection::class, $books);
        $this->assertCount(2, $books, 'Author should have two books');

        $this->assertEquals($nb + 1, $this->con->getQueryCount(), 'no new query to get the books');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testParamConvertWithOptionWithFindPk(): void
    {
        $this->loadFixtures();

        $paramConverter = new PropelParamConverter();
        $request = new Request([], [], ['id' => 10, 'author' => null]);
        $request->attributes->set('propel_converter', ['author' => ['with' => [['MyBook', 'left join']]]]);
        $argument = new ArgumentMetadata('author', 'Propel\Bundle\PropelBundle\Tests\Request\ParamConverter\MyAuthor', isVariadic: false, hasDefaultValue: false, defaultValue: null, isNullable: false);
        $nb = $this->con->getQueryCount();
        $paramConverter->resolve($request, $argument);

        $author = $request->attributes->get('author');
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Request\ParamConverter\MyAuthor', $author,
            'param "author" should be an instance of "Propel\Bundle\PropelBundle\Tests\Request\ParamConverter\MyAuthor"');

        $this->assertEquals($nb + 1, $this->con->getQueryCount(), 'only one query to get the book');

        $books = $author->getMyBooks();
        $this->assertInstanceOf(ObjectCollection::class, $books);
        $this->assertCount(2, $books, 'Author should have two books');

        $this->assertEquals($nb + 1, $this->con->getQueryCount(), 'no new query to get the books');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testConfigurationReadFromRequestAttributesIfEmpty(): void
    {
        $this->loadFixtures();

        $paramConverter = new PropelParamConverter();

        $request = new Request();
        $request->attributes->add([
            '_route' => 'test_route',
            'id' => 10,
            'author' => null,
            'propel_converter' => [
                'author' => [
                    'mapping' => [
                        'authorId' => 'id',
                    ],
                ],
            ],
        ]);

        $argument = new ArgumentMetadata('author', 'Propel\Bundle\PropelBundle\Tests\Request\ParamConverter\MyAuthor', isVariadic: false, hasDefaultValue: false, defaultValue: null);
        $paramConverter->resolve($request, $argument);

        $author = $request->attributes->get('author');
        $this->assertInstanceOf('Propel\Bundle\PropelBundle\Tests\Request\ParamConverter\MyAuthor', $author,
            'param "author" should be an instance of "Propel\Bundle\PropelBundle\Tests\Request\ParamConverter\MyAuthor"');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testSymfonyResolvesControllerArgumentsUsingRegisteredService(): void
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../../Resources/config'));
        $loader->load('converters.yml');
        $services = $container->findTaggedServiceIds('controller.argument_value_resolver');
        $this->assertCount(1, $services);

        $resolver = new ArgumentResolver(argumentValueResolvers: array_merge(
            [$container->get(array_key_first($services))],
            [...ArgumentResolver::getDefaultArgumentValueResolvers()]
        ));
        $request = new Request([], [], ['id' => 1]);
        $controller = static function (Book $book, string $format = 'html'): void {
        };

        $arguments = $resolver->getArguments($request, $controller);

        $this->assertInstanceOf(Book::class, $arguments[0]);
        $this->assertSame(1, $arguments[0]->getId());
        $this->assertSame('html', $arguments[1]);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testResolverPreservesExistingModelArgument(): void
    {
        $book = new Book();
        $request = new Request([], [], ['book' => $book]);
        $argument = new ArgumentMetadata('book', Book::class, isVariadic: false, hasDefaultValue: false, defaultValue: null);

        $this->assertSame([$book], new PropelParamConverter()->resolve($request, $argument));
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testResolverCanBeReusedAfterMappedJoinedArgument(): void
    {
        $this->loadFixtures();
        $resolver = new PropelParamConverter();
        $request = new Request([], [], [
            'authorId' => 10,
            'propel_converter' => ['author' => [
                'mapping' => ['authorId' => 'id'],
                'with' => [['MyBook', 'left join']],
            ]],
        ]);
        $argument = new ArgumentMetadata('author', MyAuthor::class, isVariadic: false, hasDefaultValue: false, defaultValue: null);
        $this->assertInstanceOf(MyAuthor::class, $resolver->resolve($request, $argument)[0]);

        $request = new Request([], [], ['id' => 1]);
        $argument = new ArgumentMetadata('book', Book::class, isVariadic: false, hasDefaultValue: false, defaultValue: null);
        $book = $resolver->resolve($request, $argument)[0];

        $this->assertInstanceOf(Book::class, $book);
        $this->assertSame(1, $book->getId());
    }
}
