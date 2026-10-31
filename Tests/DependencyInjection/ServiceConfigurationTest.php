<?php

namespace Propel\Bundle\PropelBundle\Tests\DependencyInjection;

use Exception;
use PHPUnit\Framework\TestCase;
use Propel\Bundle\PropelBundle\DependencyInjection\PropelExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

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

        new PropelExtension()->load([[
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
    public function testOptionalServicesUseNullWhenMissing(): void
    {
        $container = new ContainerBuilder();
        $locator = new FileLocator(__DIR__ . '/../../Resources/config');
        new PhpFileLoader($container, $locator)->load('propel.php');

        self::assertSame('default', $container->getParameter('propel.dbal.default_connection'));
        self::assertTrue($container->getDefinition('propel.logger')->isPublic());
        self::assertEquals([
            new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('debug.stopwatch', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ], $container->getDefinition('propel.logger')->getArguments());
        self::assertEquals([
            '%kernel.project_dir%',
            '%propel.configuration%',
            new Reference('faker.generator', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ], $container->getDefinition('propel.loader.yaml')->getArguments());
        self::assertEquals([
            new Reference('service_container'),
            new Reference('file_locator'),
            '%propel.configuration%',
        ], $container->getDefinition('propel.schema_locator')->getArguments());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testServicesExposeTheirIntegrationTags(): void
    {
        $container = new ContainerBuilder();
        $locator = new FileLocator(__DIR__ . '/../../Resources/config');
        new PhpFileLoader($container, $locator)->load('propel.php');
        $loader = new YamlFileLoader($container, $locator);
        $loader->load('converters.yml');
        $loader->load('security.yml');

        self::assertSame([['channel' => 'propel']], $container->getDefinition('propel.logger')->getTag('monolog.logger'));
        self::assertSame([
            ['template' => '@Propel/Collector/propel', 'id' => 'propel'],
        ], $container->getDefinition('propel.data_collector')->getTag('data_collector'));
        self::assertFalse($container->getDefinition('propel.data_collector')->isPublic());
        self::assertSame([[]], $container->getDefinition('propel.twig.extension.syntax')->getTag('twig.extension'));
        self::assertSame([[]], $container->getDefinition('form.type_guesser.propel')->getTag('form.type_guesser'));
        self::assertSame([['alias' => 'model']], $container->getDefinition('propel.form.type.model')->getTag('form.type'));
        self::assertSame([
            ['priority' => 110],
        ], $container->getDefinition('propel.converter.propel.orm')->getTag('controller.argument_value_resolver'));
        self::assertTrue($container->getDefinition('propel.security.user.provider')->isAbstract());
        self::assertFalse($container->getDefinition('propel.security.user.provider')->isPublic());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testConsoleCommandsAreRegisteredWithContainerBinding(): void
    {
        $container = new ContainerBuilder();
        $locator = new FileLocator(__DIR__ . '/../../Resources/config');
        new YamlFileLoader($container, $locator)->load('console.yml');

        $commands = [
            'build', 'database:create', 'database:drop', 'database:reverse',
            'fixtures:dump', 'fixtures:load', 'form:generate', 'graphviz:generate',
            'migration:diff', 'migration:down', 'migration:migrate', 'migration:status', 'migration:up',
            'model:build', 'sql:build', 'sql:insert', 'table:drop',
        ];
        self::assertCount(count($commands), $container->findTaggedServiceIds('console.command'));

        foreach ($commands as $command) {
            $id = 'propel_bundle_propel.command.' . str_replace(':', '_', $command) . '_command';
            $definition = $container->getDefinition($id);
            self::assertSame([['command' => 'propel:' . $command]], $definition->getTag('console.command'), $id);
            self::assertTrue($definition->isAutowired(), $id);
            self::assertTrue($definition->isAutoconfigured(), $id);
            self::assertFalse($definition->isPublic(), $id);
            $binding = $definition->getBindings()['$container']->getValues()[0];
            self::assertInstanceOf(Reference::class, $binding);
            self::assertSame('service_container', (string)$binding);
            self::assertSame(ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $binding->getInvalidBehavior());
        }
    }
}
