<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Command;

use Override;
use Propel\Generator\Command\MigrationStatusCommand as BaseMigrationCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

/**
 * # MigrationStatusCommand
 * @author Kévin Gomez <contact@kevingomez.fr>
 * @api
 */
#[AsCommand(name: 'propel:migration:status', description: 'Get migration status')]
class MigrationStatusCommand extends WrappedCommand
{
    /**
     * @return void
     * @throws InvalidArgumentException
     */
    #[Override]
    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption('connection', mode: InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, description: 'Connection to use. Example: default, bookstore')
            ->addOption('migration-table', mode: InputOption::VALUE_OPTIONAL, description: 'Migration table name (if none given, the configured table is used)')
            ->addOption('output-dir', mode: InputOption::VALUE_OPTIONAL, description: 'The output directory');
    }

    /**
     * @return Command
     * @throws LogicException
     */
    #[Override]
    protected function createSubCommandInstance(): Command
    {
        return new BaseMigrationCommand();
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
        $defaultOutputDir = $config['paths']['migrationDir'];

        return [
            '--connection' => $this->getConnections($input->getOption('connection')),
            '--migration-table' => $input->getOption('migration-table') ?: $this->getMigrationsTable(),
            '--output-dir' => $input->getOption('output-dir') ?: $defaultOutputDir,
        ];
    }
}
