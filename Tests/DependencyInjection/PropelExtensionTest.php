<?php

namespace Propel\Bundle\PropelBundle\Tests\DependencyInjection;

use Exception;
use PHPUnit\Framework\TestCase;
use Propel\Bundle\PropelBundle\DependencyInjection\PropelExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpKernel\Kernel;

/**
 * # PropelExtensionTest
 */
class PropelExtensionTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testLoadServices(): void
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.debug' => false,
            'kernel.environment' => 'test',
            'kernel.project_dir' => dirname(__DIR__, 2),
        ]));

        (new PropelExtension())->load([[
            'database' => [
                'connections' => [
                    'default' => [
                        'adapter' => 'sqlite',
                        'dsn' => 'sqlite::memory:',
                        'user' => '',
                        'password' => '',
                    ],
                ],
            ],
        ]], $container);

        $this->assertTrue($container->hasDefinition('propel.schema_locator'));
        $this->assertTrue($container->hasDefinition('propel.schema_converter'));
        $this->assertTrue($container->hasDefinition('propel.converter.propel.orm'));
        $this->assertTrue($container->hasDefinition('propel.security.user.provider'));
        $this->assertTrue($container->hasDefinition('propel_bundle_propel.command.build_command'));
        $this->assertTrue($container->hasDefinition('propel_bundle_propel.command.schema_convert_command'));
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testYamlServices(): void
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.project_dir' => dirname(__DIR__, 2),
            'propel.configuration' => [],
        ]));
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 2) . '/Resources/config'));
        $resources = ['propel.yaml', 'converters.yaml', 'security.yaml', 'console.yaml', 'services.yaml'];

        foreach ($resources as $resource) {
            $loader->load("yaml/$resource");
        }

        $this->assertTrue($container->hasDefinition('propel.schema_locator'));
        $this->assertTrue($container->hasDefinition('propel.schema_converter'));
        $this->assertTrue($container->hasDefinition('propel.converter.propel.orm'));
        $this->assertTrue($container->hasDefinition('propel.security.user.provider'));
        $this->assertTrue($container->hasDefinition('propel_bundle_propel.command.build_command'));
        $this->assertTrue($container->hasDefinition('propel_bundle_propel.command.schema_convert_command'));
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testPhpServices(): void
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.project_dir' => dirname(__DIR__, 2),
            'propel.configuration' => [],
        ]));
        $loader = new PhpFileLoader($container, new FileLocator(dirname(__DIR__, 2) . '/Resources/config'));
        $resources = ['propel.php', 'converters.php', 'security.php', 'console.php', 'services.php'];

        foreach ($resources as $resource) {
            $loader->load("php/$resource");
        }

        $this->assertTrue($container->hasDefinition('propel.schema_locator'));
        $this->assertTrue($container->hasDefinition('propel.schema_converter'));
        $this->assertTrue($container->hasDefinition('propel.converter.propel.orm'));
        $this->assertTrue($container->hasDefinition('propel.security.user.provider'));
        $this->assertTrue($container->hasDefinition('propel_bundle_propel.command.build_command'));
        $this->assertTrue($container->hasDefinition('propel_bundle_propel.command.schema_convert_command'));
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testXmlServices(): void
    {
        if (Kernel::VERSION_ID >= 80000) {
            $this->markTestSkipped('XmlFileLoader is no longer available since Symfony 8. Skipping...');
        }

        $container = new ContainerBuilder(new ParameterBag([
            'kernel.project_dir' => dirname(__DIR__, 2),
            'propel.configuration' => [],
        ]));
        $loader = new XmlFileLoader($container, new FileLocator(dirname(__DIR__, 2) . '/Resources/config'));
        $resources = ['propel.xml', 'converters.xml', 'security.xml', 'console.xml', 'services.xml'];

        foreach ($resources as $resource) {
            $loader->load("xml/$resource");
        }

        $this->assertTrue($container->hasDefinition('propel.schema_locator'));
        $this->assertTrue($container->hasDefinition('propel.schema_converter'));
        $this->assertTrue($container->hasDefinition('propel.converter.propel.orm'));
        $this->assertTrue($container->hasDefinition('propel.security.user.provider'));
        $this->assertTrue($container->hasDefinition('propel_bundle_propel.command.build_command'));
        $this->assertTrue($container->hasDefinition('propel_bundle_propel.command.schema_convert_command'));
    }
}
