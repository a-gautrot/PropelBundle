<?php

namespace Propel\Bundle\PropelBundle\Tests\DependencyInjection;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use Propel\Bundle\PropelBundle\DependencyInjection\PropelExtension;
use Propel\Bundle\PropelBundle\Generator\Builder\ObjectBuilder;
use Propel\Bundle\PropelBundle\Generator\Platform\OraclePlatform;
use Propel\Bundle\PropelBundle\Tests\TestCase;

/**
 *
 */
class OracleConfigurationTest extends TestCase
{
    /**
     * @return iterable
     */
    public static function configurations(): iterable
    {
        yield 'Oracle defaults' => [['default' => 'oracle'], [], OraclePlatform::class, ObjectBuilder::class];
        yield 'mixed engines' => [['default' => 'oracle', 'other' => 'mysql'], [], null, ObjectBuilder::class];
        yield 'explicit overrides' => [['default' => 'oracle'], [
            'platformClass' => 'App\\OraclePlatform',
            'objectModel' => ['builders' => ['object' => 'App\\ObjectBuilder']],
        ], 'App\\OraclePlatform', 'App\\ObjectBuilder'];
        yield 'MySQL defaults' => [['default' => 'mysql'], [], null, \Propel\Generator\Builder\Om\ObjectBuilder::class];
    }

    /**
     * @param array $adapters
     * @param array $generator
     * @param string|null $expectedPlatform
     * @param string $expectedBuilder
     * @return void
     * @throws Exception
     */
    #[DataProvider(methodName: 'configurations')]
    public function testGeneratorDefaultsRespectDatabaseEnginesAndExplicitOverrides(
        array   $adapters,
        array   $generator,
        ?string $expectedPlatform,
        string  $expectedBuilder
    ): void
    {
        $connections = [];
        foreach ($adapters as $name => $adapter) {
            $connections[$name] = [
                'adapter' => $adapter, 'dsn' => 'unused:dsn', 'user' => 'test', 'password' => 'test',
            ];
        }
        $container = $this->getContainer();
        new PropelExtension()->load([[
            'database' => ['connections' => $connections], 'generator' => $generator,
        ]], $container);
        $config = $container->getParameter('propel.configuration');

        self::assertSame($expectedPlatform, $config['generator']['platformClass']);
        self::assertSame($expectedBuilder, ltrim($config['generator']['objectModel']['builders']['object'], '\\'));
    }
}
