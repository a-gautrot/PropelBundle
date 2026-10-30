<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Command;

use InvalidArgumentException;
use Propel\Generator\Command\AbstractCommand as PropelAbstractCommand;
use Propel\Runtime\Connection\ConnectionManagerSingle;
use Propel\Runtime\Connection\Exception\ConnectionException;
use Propel\Runtime\Propel;
use Propel\Runtime\ServiceContainer\StandardServiceContainer;
use RuntimeException;
use Symfony\Component\Console\Exception\InvalidArgumentException as ConsoleInvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * # DatabaseCreateCommand
 * Useful to create a database.
 *
 * @author William DURAND
 * @api
 */
class DatabaseCreateCommand extends AbstractCommand
{
    /**
     * @return void
     * @throws ConsoleInvalidArgumentException
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure(): void
    {
        $this
            ->setName('propel:database:create')
            ->setDescription('Create a given database or the default one.')
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a connection to use');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws InvalidArgumentException When the target directory does not exist
     * @throws ConnectionException
     * @throws RuntimeException
     * @see \Symfony\Component\Console\Command\Command
     *
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connectionName = $input->getOption('connection') ?: $this->getDefaultConnection();
        $config = $this->getConnectionData($connectionName);
        $dbName = $this->parseDbName($config['dsn']);

        if ($dbName === null) {
            $output->writeln('<error>No database name found.</error>');

            return PropelAbstractCommand::CODE_ERROR;
        } else {
            $query = "CREATE DATABASE $dbName;";
        }

        $manager = new ConnectionManagerSingle($connectionName);
        $manager->setConfiguration($this->getTemporaryConfiguration($config));

        /** @var StandardServiceContainer $serviceContainer */
        $serviceContainer = Propel::getServiceContainer();
        $serviceContainer->setAdapterClass($connectionName, $config['adapter']);
        $serviceContainer->setConnectionManager($manager);

        $connection = Propel::getConnection($connectionName);

        $statement = $connection->prepare($query);
        if ($statement === false) {
            throw new RuntimeException('Cannot prepare the database query.');
        }
        $statement->execute();

        $output->writeln("<info>Database <comment>$dbName</comment> has been created.</info>");

        // s 5.1 expect integer to be returned. Introduces Command::SUCCESS and Command::FAILURE constants
        return PropelAbstractCommand::CODE_SUCCESS;
    }

    /**
     * Create a temporary configuration to connect to the database in order
     * to create a given database. This idea comes from Doctrine1.
     *
     * @see https://github.com/doctrine/doctrine1/blob/master/lib/Doctrine/Connection.php#L1491
     *
     * @param array<string, mixed> $config A Propel connection configuration.
     * @return array<string, mixed>
     */
    private function getTemporaryConfiguration(array $config): array
    {
        $dbName = $this->parseDbName($config['dsn']);

        $config['dsn'] = preg_replace(
            "#;?(dbname|Database)=$dbName#",
            '',
            $config['dsn']
        );

        return $config;
    }
}
