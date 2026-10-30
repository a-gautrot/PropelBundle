<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Command;

use Propel\Generator\Command\MigrationMigrateCommand as BaseMigrationCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

/**
 * # MigrationMigrateCommand
 * @author Kévin Gomez <contact@kevingomez.fr>
 * @api
 */
class MigrationMigrateCommand extends WrappedCommand
{
    /**
     * @return void
     * @throws InvalidArgumentException
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('propel:migration:migrate')
            ->setDescription('Execute all pending migrations')
            ->addOption('connection', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Connection to use. Example: default, bookstore')
            ->addOption('migration-table', null, InputOption::VALUE_OPTIONAL, 'Migration table name (if none given, the configured table is used)', null)
            ->addOption('output-dir', null, InputOption::VALUE_OPTIONAL, 'The output directory')
            ->addOption('fake', null, InputOption::VALUE_NONE, 'Does not touch the actual schema, but marks all migration as executed.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Continues with the migration even when errors occur.');
    }

    /**
     * @return Command
     * @throws LogicException
     */
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
