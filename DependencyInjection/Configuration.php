<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\DependencyInjection;

use InvalidArgumentException;
use Override;
use Propel\Common\Config\PropelConfiguration;
use Propel\Runtime\Connection\ConnectionWrapper;
use Propel\Runtime\Connection\DebugPDO;
use Propel\Runtime\Util\Profiler;
use RuntimeException;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * # Configuration
 * This class contains the configuration information for the bundle
 * @api
 */
class Configuration extends PropelConfiguration
{
    /**
     * @var bool
     */
    private bool $debug;
    /**
     * @var string
     */
    private string $defaultDir;
    /**
     * @var string
     */
    private string $kernelDir;

    /**
     * @param bool $debug
     * @param string $kernelDir
     */
    public function __construct(bool $debug, string $kernelDir)
    {
        $this->debug = $debug;
        $this->defaultDir = "$kernelDir/propel";
        $this->kernelDir = $kernelDir;
    }

    /**
     * @param ArrayNodeDefinition $node
     * @return void
     * @psalm-suppress PossiblyNullReference Symfony's end() returns the configured parent builder.
     * @psalm-suppress UndefinedInterfaceMethod Symfony's fluent builder loses the concrete parent type at end().
     */
    #[Override]
    protected function addPathsSection(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('paths')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('schemaDir')->defaultValue($this->defaultDir)->end()
                        ->scalarNode('sqlDir')->defaultValue($this->defaultDir . '/sql')->end()
                        ->scalarNode('migrationDir')->defaultValue($this->defaultDir . '/migrations')->end()
                        ->scalarNode('composerDir')->defaultNull()->end()
                        ->scalarNode('loaderScriptDir')->defaultValue($this->kernelDir . '/generated-conf')->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param ArrayNodeDefinition $node
     * @return void
     * @psalm-suppress PossiblyNullReference Symfony's end() returns the configured parent builder.
     * @psalm-suppress UndefinedInterfaceMethod Symfony's fluent builder loses the concrete parent type at end().
     */
    #[Override]
    protected function addRuntimeSection(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('runtime')
                    ->addDefaultsIfNotSet()
                    ->fixXmlConfig('connection')
                    ->children()
                        ->scalarNode('defaultConnection')->end()
                        ->arrayNode('connections')
                            ->prototype('scalar')->end()
                        ->end()
                        ->booleanNode('logging')->defaultValue($this->debug)->end()
                        ->arrayNode('log')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('type')->end()
                                    ->scalarNode('path')->end()
                                    ->enumNode('level')->values([100, 200, 250, 300, 400, 500, 550, 600])->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('profiler')
                            ->children()
                                ->scalarNode('classname')->defaultValue(Profiler::class)->end()
                                ->floatNode('slowTreshold')->defaultValue(0.1)->end()
                                ->arrayNode('details')
                                    ->children()
                                        ->arrayNode('time')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->integerNode('precision')->min(0)->defaultValue(3)->end()
                                                ->integerNode('pad')->min(0)->defaultValue(8)->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('memory')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->integerNode('precision')->min(0)->defaultValue(3)->end()
                                                ->integerNode('pad')->min(0)->defaultValue(8)->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('memDelta')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->integerNode('precision')->min(0)->defaultValue(3)->end()
                                                ->integerNode('pad')->min(0)->defaultValue(8)->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('memPeak')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->integerNode('precision')->min(0)->defaultValue(3)->end()
                                                ->integerNode('pad')->min(0)->defaultValue(8)->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                                ->scalarNode('innerGlue')->defaultValue(':')->end()
                                ->scalarNode('outerGlue')->defaultValue('|')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end() //runtime
            ->end();
    }

    /**
     * @param ArrayNodeDefinition $node
     * @return void
     * @psalm-suppress UndefinedMethod ExprBuilder::end() returns the connections ArrayNodeDefinition.
     * @throws RuntimeException
     */
    #[Override]
    protected function addDatabaseSection(ArrayNodeDefinition $node): void
    {
        $validAdapters = ['mysql', 'pgsql', 'sqlite', 'mssql', 'sqlsrv', 'oracle'];

        $node
            ->children()
                ->arrayNode('database')
                    ->isRequired()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('connections')
                            ->isRequired()
                            ->validate()
                            ->always()
                                ->then(function (array $connections): array {
                                    foreach (array_keys($connections) as $name) {
                                        if (str_contains($name, '.')) {
                                            throw new InvalidArgumentException(
                                                'Dots are not allowed in connection names'
                                            );
                                        }
                                    }

                                    return $connections;
                                })
                            ->end()
                            ->requiresAtLeastOneElement()
                            ->normalizeKeys(false)
                            ->useAttributeAsKey('id')
                            ->prototype('array')
                            ->fixXmlConfig('slave')
                            ->fixXmlConfig('model_path')
                                ->children()
                                    ->scalarNode('classname')
                                        ->defaultValue($this->debug ? DebugPDO::class : ConnectionWrapper::class)
                                    ->end()
                                    ->scalarNode('adapter')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then(
                                                fn(string $v): ?string => preg_replace('/^pdo_/', '', strtolower($v))
                                            )
                                        ->end()
                                        ->validate()
                                            ->ifNotInArray($validAdapters)
                                            ->thenInvalid(
                                                'The adapter %s is not supported. Please choose one of '
                                                . implode(', ', $validAdapters)
                                            )
                                        ->end()
                                    ->end()
                                    ->scalarNode('dsn')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then(fn(string $v): ?string => preg_replace('/^pdo_/', '', $v))
                                        ->end()
                                    ->end()
                                    ->scalarNode('user')->isRequired()->end()
                                    ->scalarNode('password')->isRequired()->treatNullLike('')->end()
                                    ->arrayNode('options')
                                        ->addDefaultsIfNotSet()
                                        ->children()
                                            ->booleanNode('ATTR_PERSISTENT')->defaultFalse()->end()
                                            ->scalarNode('MYSQL_ATTR_SSL_CA')->end()
                                            ->scalarNode('MYSQL_ATTR_SSL_CERT')->end()
                                            ->scalarNode('MYSQL_ATTR_SSL_KEY')->end()
                                            ->scalarNode('MYSQL_ATTR_MAX_BUFFER_SIZE')->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('attributes')
                                        ->addDefaultsIfNotSet()
                                        ->children()
                                            ->booleanNode('ATTR_EMULATE_PREPARES')->defaultFalse()->end()
                                            ->scalarNode('SQLSRV_ATTR_ENCODING')->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('model_paths')
                                        ->defaultValue(['src', 'vendor'])
                                        ->prototype('scalar')->end()
                                    ->end()
                                    ->arrayNode('settings')
                                    ->fixXmlConfig('query', 'queries')
                                        ->children()
                                            ->scalarNode('charset')->defaultValue('utf8')->end()
                                            ->arrayNode('queries')
                                                ->prototype('scalar')->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('slaves')
                                        ->prototype('array')
                                            ->children()
                                                ->scalarNode('dsn')
                                                    ->beforeNormalization()
                                                    ->ifString()
                                                    ->then(fn(string $v): ?string => preg_replace('/^pdo_/', '', $v))
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('adapters')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('mysql')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('tableType')
                                            ->defaultValue('InnoDB')
                                            ->treatNullLike('InnoDB')
                                        ->end()
                                        ->scalarNode('tableEngineKeyword')->defaultValue('ENGINE')->end()
                                        ->scalarNode('uuidColumnType')->defaultValue('binary')->end()
                                    ->end()
                                ->end()
                                ->arrayNode('sqlite')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('foreignKey')->end()
                                        ->scalarNode('tableAlteringWorkaround')->end()
                                    ->end()
                                ->end()
                                ->arrayNode('oracle')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('autoincrementSequencePattern')
                                            ->defaultValue('${table}_SEQ')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end() //adapters
                    ->end()
                ->end() //database
            ->end();
    }
}
