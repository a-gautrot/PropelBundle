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
use PDO;
use Propel\Generator\Command\AbstractCommand as PropelAbstractCommand;
use Propel\Runtime\Adapter\Pdo\MysqlAdapter;
use Propel\Runtime\Connection\Exception\ConnectionException;
use Propel\Runtime\Propel;
use RuntimeException;
use Symfony\Component\Console\Exception\InvalidArgumentException as ConsoleInvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * # TableDropCommand
 * @author Kévin Gomez <contact@kevingomez.fr>
 * @api
 */
class TableDropCommand extends AbstractCommand
{
    use FormattingHelpers;

    /**
     * @return void
     * @throws ConsoleInvalidArgumentException
     */
    protected function configure(): void
    {
        $this
            ->setName('propel:table:drop')
            ->setDescription('Drop a given table or all tables in the database.')
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'Connection to use. Example: default, bookstore')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action.')
            ->addArgument('table', InputArgument::IS_ARRAY, 'Set this parameter to défine which table to delete (default all the table in the database.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws ConnectionException
     * @throws RuntimeException
     * @throws ConsoleInvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = Propel::getConnection($input->getOption('connection'));
        $adapter = Propel::getAdapter($connection->getName());

        if (!$adapter instanceof MysqlAdapter) {
            $output->writeln('<error>This command is MySQL only.</error>');

            return PropelAbstractCommand::CODE_ERROR;
        }

        if (!$input->getOption('force')) {
            $output->writeln('<error>You have to use the "--force" option to drop some tables.</error>');

            return PropelAbstractCommand::CODE_ERROR;
        }

        $tablesToDelete = $input->getArgument('table');
        $nbTable = count($tablesToDelete);
        $tablePlural = (($nbTable > 1 || $nbTable == 0) ? 's' : '');

        if ($this->getKernel()->getEnvironment() === 'prod') {
            $count = $nbTable ?: 'all';

            $this->writeSection(
                $output,
                "WARNING: you are about to drop $count table$tablePlural in production !",
                'bg=red;fg=white'
            );

            if ($this->askConfirmation($input, $output, 'Are you sure ? (y/n) ', false) === false) {
                $output->writeln('<info>Aborted, nice decision !</info>');

                return -2;
            }
        }

        $showStatement = $connection->prepare('SHOW TABLES;');
        if ($showStatement === false) {
            throw new RuntimeException('Cannot list the database tables.');
        }
        $showStatement->execute();

        $allTables = $showStatement->fetchAll(PDO::FETCH_COLUMN);

        if ($nbTable) {
            foreach ($tablesToDelete as $tableToDelete) {
                if (!in_array($tableToDelete, $allTables, true)) {
                    throw new InvalidArgumentException("Table $tableToDelete doesn't exist in the database.");
                }
            }
        } else {
            $tablesToDelete = $allTables;
        }

        $connection->exec('SET FOREIGN_KEY_CHECKS = 0;');

        array_walk($tablesToDelete, function (string &$table, $key, MysqlAdapter $dbAdapter): void {
            $table = $dbAdapter->quoteIdentifierTable($table);
        }, $adapter);

        $tablesToDelete = implode(', ', $tablesToDelete);

        if ($tablesToDelete !== '') {
            $connection->exec("DROP TABLE $tablesToDelete ;");

            $output->writeln("Table$tablePlural <info><comment>$tablesToDelete</comment> has been dropped.</info>");
        } else {
            $output->writeln('<info>No tables have been dropped</info>');
        }

        $connection->exec('SET FOREIGN_KEY_CHECKS = 1;');

        return PropelAbstractCommand::CODE_SUCCESS;
    }
}
