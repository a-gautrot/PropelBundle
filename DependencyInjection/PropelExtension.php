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
use LogicException;
use Symfony\Bundle\WebProfilerBundle\DependencyInjection\WebProfilerExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\Profiler\Profiler;

/**
 * # PropelExtension
 *
 * PropelExtension loads the PropelBundle configuration.
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
class PropelExtension extends Extension
{
    /**
     * @param array $configs
     * @param ContainerBuilder $container
     * @return void
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        if (count($config['database']['connections']) === 1) {
            $defaultConnection = array_keys($config['database']['connections'])[0];
            if (!isset($config['runtime']['defaultConnection'])) {
                $config['runtime']['defaultConnection'] = $defaultConnection;
            }
            if (!isset($config['generator']['defaultConnection'])) {
                $config['generator']['defaultConnection'] = $defaultConnection;
            }
        }

        $container->setParameter('propel.logging', $config['runtime']['logging']);
        $container->setParameter('propel.configuration', $config);

        // Load services
        if (!$container->hasDefinition('propel')) {
            $resources = ['propel', 'converters', 'security', 'console', 'services'];
            $configDir = __DIR__ . '/../Resources/config';
            $locator = new FileLocator($configDir);

            if ($this->hasResources("$configDir/yaml", $resources)) {
                $loader = new YamlFileLoader($container, $locator);

                foreach ($resources as $resource) {
                    $loader->load("yaml/$resource.yaml");
                }
            } elseif ($this->hasResources("$configDir/php", $resources, 'php')) {
                $loader = new PhpFileLoader($container, $locator);

                foreach ($resources as $resource) {
                    $loader->load("php/$resource.php");
                }
            } elseif (Kernel::VERSION_ID < 80000) {
                // Legacy fallback when neither YAML nor PHP service files are available
                $loader = new XmlFileLoader($container, $locator);

                foreach ($resources as $resource) {
                    $loader->load("xml/$resource.xml");
                }
            } else {
                throw new LogicException('No service configuration files were found.');
            }

            if ($container->getParameter('kernel.environment') === 'dev' && class_exists(WebProfilerExtension::class)) {
                $container->setAlias(Profiler::class, 'profiler');
            }
        }
    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     *
     * @return Configuration
     */
    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration($container->getParameter('kernel.debug'), $container->getParameter('kernel.project_dir'));
    }

    /**
     * @param string $directory The services path
     * @param array $resources The service to load
     * @param string $extension The configuration file extension
     * @return bool
     */
    private function hasResources(string $directory, array $resources, string $extension = 'yaml'): bool
    {
        foreach ($resources as $resource) {
            if (!is_file("$directory/$resource.$extension")) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the base path for the XSD files.
     *
     * @return string The XSD base path
     *
     * @deprecated since Symfony 7.4, to be removed in Symfony 8.0 together with XML support.
     */
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
    public function getAlias(): string
    {
        return 'propel';
    }
}
