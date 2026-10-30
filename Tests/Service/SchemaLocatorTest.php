<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Tests\Service;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use Propel\Bundle\PropelBundle\Service\SchemaLocator;
use Propel\Bundle\PropelBundle\Tests\Fixtures\FakeBundle\FakeBundle;
use Propel\Bundle\PropelBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Component\HttpKernel\Kernel;

/**
 * # SchemaLocatorTest
 */
class SchemaLocatorTest extends TestCase
{
    /**
     * container generated for the tests
     * @var ContainerBuilder
     */
    private ContainerBuilder $container;

    /**
     * @var array<array-key, mixed>
     */
    private array $configuration;

    /**
     * @var FileLocator
     */
    private FileLocator $fileLocator;

    /**
     * @var MockObject|FakeBundle
     */
    private $bundleMock;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $pathStructure = [
            'configuration' => [
                'directory' => [
                    'schema.xml' => 'Schema from configuration'
                ]
            ],
        ];
        $root = vfsStream::setup('projectDir');
        vfsStream::create($pathStructure);

        $kernelStub = $this->createStub(Kernel::class);
        $kernelStub->method('getProjectDir')->willReturn($root->url());
        $kernelStub->method('locateResource')->willReturnCallback(fn(string $argument): string => str_replace('@', __DIR__ . '/../Fixtures/', $argument));

        // attach kernel service to container
        $this->container = $this->getContainer();
        $this->container->set('kernel', $kernelStub);

        $this->bundleMock = new FakeBundle();

        $this->configuration['paths']['schemaDir'] = vfsStream::url('projectDir/configuration/directory');
        $this->fileLocator = new FileLocator($kernelStub);
    }

    /**
     * @return void
     */
    public function testLocateFromBundle(): void
    {
        $locator = new SchemaLocator($this->container, $this->fileLocator, $this->configuration);
        $files = $locator->locateFromBundle($this->bundleMock);

        $this->assertCount(1, $files);

        $this->assertTrue(isset($files[__DIR__ . '/../Fixtures/FakeBundle/Resources/config/bundle.schema.xml']));
        $this->assertEquals('bundle.schema.xml', $files[__DIR__ . '/../Fixtures/FakeBundle/Resources/config/bundle.schema.xml'][1]->getFileName());
    }

    /**
     * @return void
     */
    public function testLocateFromBundlesAndConfiguration(): void
    {
        $locator = new SchemaLocator($this->container, $this->fileLocator, $this->configuration);
        $files = $locator->locateFromBundlesAndConfiguration(
            [$this->bundleMock]
        );

        $this->assertCount(2, $files);
        $this->assertTrue(isset($files[__DIR__ . '/../Fixtures/FakeBundle/Resources/config/bundle.schema.xml']));
        $this->assertEquals('bundle.schema.xml', $files[__DIR__ . '/../Fixtures/FakeBundle/Resources/config/bundle.schema.xml'][1]->getFileName());
        $this->assertTrue(isset($files['vfs://projectDir/configuration/directory/schema.xml']));
        $this->assertEquals('schema.xml', $files['vfs://projectDir/configuration/directory/schema.xml'][1]->getFileName());
    }
}
