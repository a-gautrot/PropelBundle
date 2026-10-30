<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Override;
use Propel\Generator\Command\MigrationUpCommand as BaseMigrationCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

/**
 * # MigrationUpCommand
 * @author Kévin Gomez <contact@kevingomez.fr>
 * @api
 */
#[AsCommand(name: 'propel:migration:up', description: 'Execute (only one) next migration')]
class MigrationUpCommand extends WrappedCommand
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
            ->addOption('output-dir', mode: InputOption::VALUE_OPTIONAL, description: 'The output directory')
            ->addOption('fake', mode: InputOption::VALUE_NONE, description: 'Does not touch the actual schema, but marks next migration as executed.')
            ->addOption('force', mode: InputOption::VALUE_NONE, description: 'Continues with the migration even when errors occur.');
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
            '--fake' => $input->getOption('fake'),
            '--force' => $input->getOption('force'),
        ];
    }
}
