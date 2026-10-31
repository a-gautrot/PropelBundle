<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle;

use Exception;
use LogicException as BaseLogicException;
use Override;
use Propel\Bundle\PropelBundle\DependencyInjection\Security\UserProvider\PropelFactory;
use Propel\Runtime\Connection\ConnectionManagerPrimaryReplica;
use Propel\Runtime\Connection\ConnectionManagerSingle;
use Propel\Runtime\Connection\ConnectionWrapper;
use Propel\Runtime\Propel;
use Propel\Runtime\ServiceContainer\StandardServiceContainer;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * # PropelBundle
 *
 * @author William DURAND <william.durand1@gmail.com>
 * @api
 */
class PropelBundle extends Bundle
{
    /**
     * @return void
     */
    #[Override]
    public function boot(): void
    {
        if ($this->container === null) {
            return;
        }

        try {
            $this->configureConnections();

            if ($this->container->getParameter('propel.logging') === true) {
                $this->configureLogging();
            }
        } catch (Exception) {
        }
    }

    /**
     * @return void
     * @throws BaseLogicException
     * @throws ParameterNotFoundException
     */
    protected function configureConnections(): void
    {
        if ($this->container === null) {
            throw new BaseLogicException('The bundle container has not been set.');
        }

        /** @var array{runtime: array{defaultConnection?: string}, database: array{connections: array<string, array<string, mixed>>}, paths: array{loaderScriptDir: string}} $config Validated by DependencyInjection\Configuration. */
        $config = $this->container->getParameter('propel.configuration');
        $defaultConnection = $config['runtime']['defaultConnection'] ?? key($config['database']['connections']);
        if ($defaultConnection === null) {
            throw new BaseLogicException('No Propel connection is configured.');
        }

        /** @var StandardServiceContainer $serviceContainer */
        $serviceContainer = Propel::getServiceContainer();
        $serviceContainer->setDefaultDatasource($defaultConnection);

        foreach ($config['database']['connections'] as $name => $connection) {
            if (!empty($connection['slaves'])) {
                $manager = new ConnectionManagerPrimaryReplica($name);

                // configure the master (write) connection
                $manager->setWriteConfiguration($connection);

                // configure the slave (read) connections
                $slaveConnections = [];
                foreach ($connection['slaves'] as $slave) {
                    $slaveConnections[] = array_merge($connection, [
                        'dsn' => $slave['dsn'],
                        'slaves' => null
                    ]);
                }

                $manager->setReadConfiguration($slaveConnections);
            } else {
                $manager = new ConnectionManagerSingle($name);
                $manager->setConfiguration($connection);
            }

            $serviceContainer->setAdapterClass($name, $connection['adapter']);
            $serviceContainer->setConnectionManager($manager);

            // load database maps
            if (file_exists("{$config['paths']['loaderScriptDir']}/loadDatabase.php")
                && is_readable("{$config['paths']['loaderScriptDir']}/loadDatabase.php")) {
                /** @psalm-suppress UnresolvableInclude The path comes from the validated application configuration. */
                require_once("{$config['paths']['loaderScriptDir']}/loadDatabase.php");
            }
        }
    }

    /**
     * @return void
     * @throws BaseLogicException
     * @throws ServiceCircularReferenceException
     * @throws ServiceNotFoundException
     */
    protected function configureLogging(): void
    {
        if ($this->container === null) {
            throw new BaseLogicException('The bundle container has not been set.');
        }

        /** @var StandardServiceContainer $serviceContainer */
        $serviceContainer = Propel::getServiceContainer();
        $logger = $this->container->get('propel.logger');
        if (!$logger instanceof LoggerInterface) {
            throw new BaseLogicException('The propel.logger service must implement LoggerInterface.');
        }
        $serviceContainer->setLogger('defaultLogger', $logger);

        foreach ($serviceContainer->getConnectionManagers() as $manager) {
            /** @var ConnectionWrapper $connection */
            $connection = $manager->getReadConnection($serviceContainer->getAdapter($manager->getName()));
            $connection->setLogMethods(array_merge($connection->getLogMethods(), ['prepare']));

            /** @var ConnectionWrapper $connection */
            $connection = $manager->getWriteConnection($serviceContainer->getAdapter($manager->getName()));
            $connection->setLogMethods(array_merge($connection->getLogMethods(), ['prepare']));
        }
    }

    /**
     * @param ContainerBuilder $container
     * @return void
     * @throws LogicException
     */
    #[Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        if ($container->hasExtension('security')) {
            /** @var SecurityExtension $securityExtension */
            $securityExtension = $container->getExtension('security');
            $securityExtension->addUserProviderFactory(new PropelFactory('propel', 'propel.security.user.provider'));
        }
    }
}
