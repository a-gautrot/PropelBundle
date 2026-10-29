<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Command;

use Propel\Generator\Command\MigrationStatusCommand as BaseMigrationCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * # MigrationStatusCommand
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class MigrationStatusCommand extends WrappedCommand
{
    /**
     * @return void
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('propel:migration:status')
            ->setDescription('Get migration status')
            ->addOption('connection', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Connection to use. Example: default, bookstore')
            ->addOption('migration-table', null, InputOption::VALUE_OPTIONAL, 'Migration table name (if none given, the configured table is used)', null)
            ->addOption('output-dir', null, InputOption::VALUE_OPTIONAL, 'The output directory');
    }

    /**
     * @return Command
     */
    protected function createSubCommandInstance(): Command
    {
        return new BaseMigrationCommand();
    }

    /**
     * @param InputInterface $input
     * @return array
     */
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
