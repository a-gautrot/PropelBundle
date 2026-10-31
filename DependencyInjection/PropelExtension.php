<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\DependencyInjection;

use Exception;
use InvalidArgumentException;
use Override;
use Symfony\Bundle\WebProfilerBundle\DependencyInjection\WebProfilerExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\Profiler\Profiler;

/**
 * PropelExtension loads the PropelBundle configuration.
 *
 * @author William DURAND <william.durand1@gmail.com>
 * @api
 */
class PropelExtension extends Extension
{
    /**
     * @param array<array-key, mixed> $configs
     * @param ContainerBuilder $container
     * @return void
     *
     * @throws Exception
     */
    #[Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        if (count($config['database']['connections']) === 1) {
            $defaultConnection = array_keys($config['database']['connections'])[0];
            $config['runtime']['defaultConnection'] ??= $defaultConnection;
            $config['generator']['defaultConnection'] ??= $defaultConnection;
        }

        $container->setParameter('propel.logging', $config['runtime']['logging']);
        $container->setParameter('propel.configuration', $config);

        // Load services
        if (!$container->hasDefinition('propel')) {
            /** @psalm-suppress DeprecatedMethod XML services remain supported on the required Symfony 6.4. */
            $this->loadXmlResources($container);

            $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
            $loader->load('services.yml');

            if ($container->getParameter('kernel.environment') === 'dev' && class_exists(WebProfilerExtension::class)) {
                $container->setAlias(Profiler::class, 'profiler');
            }
        }
    }

    /**
     * @param array<array-key, mixed> $config
     * @param ContainerBuilder $container
     *
     * @return Configuration
     * @throws InvalidArgumentException
     * @throws ParameterNotFoundException
     */
    #[Override]
    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        $debug = $container->getParameter('kernel.debug');
        $projectDir = $container->getParameter('kernel.project_dir');
        if (!is_bool($debug) || !is_string($projectDir)) {
            throw new InvalidArgumentException(
                'The kernel.debug and kernel.project_dir parameters have invalid types.'
            );
        }

        return new Configuration($debug, $projectDir);
    }

    /**
     * Loads the legacy XML service definitions.
     *
     * @param ContainerBuilder $container
     * @return void
     * @throws Exception
     */
    private function loadXmlResources(ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('propel.xml');
        $loader->load('converters.xml');
        $loader->load('security.xml');
        $loader->load('console.xml');
    }

    /**
     * Returns the base path for the XSD files.
     *
     * @return string The XSD base path
     *
     * @deprecated since Symfony 7.4, to be removed in Symfony 8.0 together with XML support.
     */
    #[Override]
    public function getXsdValidationBasePath(): string
    {
        return __DIR__ . '/../Resources/config/schema';
    }

    /**
     * Returns the recommended alias to use in XML.
     *
     * This alias is also the mandatory prefix to use when using YAML.
     *
     * @return string The alias
     */
    #[Override]
    public function getAlias(): string
    {
        return 'propel';
    }
}
