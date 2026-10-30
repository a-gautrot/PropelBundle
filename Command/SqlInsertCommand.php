<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Command;

use JsonException;
use LogicException;
use Override;
use Propel\Generator\Command\SqlInsertCommand as PropelSqlInsertCommand;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException as ConsoleLogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

/**
 * # SqlInsertCommand
 * @author Kévin Gomez <contact@kevingomez.fr>
 * @api
 */
#[AsCommand(name: 'propel:sql:insert', description: 'Insert SQL statements')]
class SqlInsertCommand extends WrappedCommand
{
    /**
     * @return void
     * @throws InvalidArgumentException
     */
    #[Override]
    protected function configure(): void
    {
        $this
            ->addOption('force', mode: InputOption::VALUE_NONE, description: 'Set this parameter to execute this action.')
            ->addOption('sql-dir', mode: InputOption::VALUE_REQUIRED, description: 'The SQL files directory')
            ->addOption('connection', mode: InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, description: 'Connection to use. Example: default, bookstore');
    }

    /**
     * @return Command
     * @throws ConsoleLogicException
     */
    #[Override]
    protected function createSubCommandInstance(): Command
    {
        return new PropelSqlInsertCommand();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws ExceptionInterface
     * @throws JsonException
     * @throws LogicException
     * @throws RuntimeException
     * @throws ParameterNotFoundException
     * @throws ServiceNotFoundException
     * @throws IOException
     * @throws DirectoryNotFoundException
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('force')) {
            return parent::execute($input, $output);
        } else {
            $output->writeln('<error>You have to use --force to execute all SQL statements.</error>');
            return Command::FAILURE;
        }
    }

    /**
     * @param InputInterface $input
     * @return array<string, mixed>
     * @throws InvalidArgumentException
     * @throws ParameterNotFoundException
     */
    #[Override]
    protected function getSubCommandArguments(InputInterface $input): array
    {
        $config = $this->getConfig();
        $defaultSqlDir = $config['paths']['sqlDir'];

        return [
            '--connection' => $this->getConnections($input->getOption('connection')),
            '--sql-dir' => $input->getOption('sql-dir') ?: $defaultSqlDir,
        ];
    }
}
