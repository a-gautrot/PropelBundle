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

/**
 * # ServiceConfigurationTest
 */
class ServiceConfigurationTest extends TestCase
{
    /**
     * @return void
     * @throws Exception
     */
    public function testExtensionLoadsServiceDefinitions(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', value: false);
        $container->setParameter('kernel.project_dir', dirname(__DIR__, 2));
        $container->setParameter('kernel.environment', 'prod');

        (new PropelExtension())->load([[
            'database' => ['connections' => ['default' => [
                'adapter' => 'sqlite',
                'dsn' => 'sqlite::memory:',
                'user' => '',
                'password' => '',
            ]]],
        ]], $container);

        self::assertTrue($container->hasDefinition('propel.schema_locator'));
        self::assertTrue($container->hasDefinition('propel.logger'));
        self::assertTrue($container->hasDefinition('propel.converter.propel.orm'));
        self::assertTrue($container->hasDefinition('propel.security.user.provider'));
        self::assertTrue($container->hasDefinition('propel_bundle_propel.command.migration_migrate_command'));
        self::assertTrue($container->hasDefinition('propel_bundle_propel.command.migration_status_command'));
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testServiceDefinitionsMatchLegacyXml(): void
    {
        $locator = new FileLocator(__DIR__ . '/../../Resources/config');

        foreach (['propel', 'converters', 'security', 'console'] as $resource) {
            $xmlContainer = new ContainerBuilder();
            $container = new ContainerBuilder();
            (new XmlFileLoader($xmlContainer, $locator))->load($resource . '.xml');
            if ($resource === 'propel') {
                (new PhpFileLoader($container, $locator))->load($resource . '.php');
            } else {
                (new YamlFileLoader($container, $locator))->load($resource . '.yml');
            }

            self::assertEquals(
                $xmlContainer->getParameterBag()->all(),
                $container->getParameterBag()->all(),
                $resource
            );
            self::assertSame(
                array_keys($xmlContainer->getDefinitions()),
                array_keys($container->getDefinitions()),
                $resource
            );

            foreach ($xmlContainer->getDefinitions() as $id => $xmlDefinition) {
                $yamlDefinition = $container->getDefinition($id);
                self::assertSame($xmlDefinition->getClass(), $yamlDefinition->getClass(), $id);
                self::assertEquals($xmlDefinition->getArguments(), $yamlDefinition->getArguments(), $id);
                self::assertSame($xmlDefinition->getTags(), $yamlDefinition->getTags(), $id);
                self::assertSame($xmlDefinition->isPublic(), $yamlDefinition->isPublic(), $id);
                self::assertSame($xmlDefinition->isAbstract(), $yamlDefinition->isAbstract(), $id);
                self::assertSame($xmlDefinition->isAutowired(), $yamlDefinition->isAutowired(), $id);
                self::assertSame($xmlDefinition->isAutoconfigured(), $yamlDefinition->isAutoconfigured(), $id);
                self::assertSame(
                    array_keys($xmlDefinition->getBindings()),
                    array_keys($yamlDefinition->getBindings()),
                    $id
                );

                foreach ($xmlDefinition->getBindings() as $key => $binding) {
                    self::assertEquals(
                        $binding->getValues()[0],
                        $yamlDefinition->getBindings()[$key]->getValues()[0],
                        $id . ': ' . $key
                    );
                }
            }
        }
    }
}
