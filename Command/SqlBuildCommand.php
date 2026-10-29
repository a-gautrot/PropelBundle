<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * # SqlBuildCommand
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class SqlBuildCommand extends WrappedCommand
{
    /**
     * @return void
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('propel:sql:build')
            ->setDescription('Build SQL files')
            ->addOption('sql-dir', null, InputOption::VALUE_REQUIRED, 'The SQL files directory')
            ->addOption('overwrite', null, InputOption::VALUE_NONE, '')
            ->addOption('connection', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Connection to use. Example: default, bookstore');
    }

    /**
     * @return Command
     */
    protected function createSubCommandInstance(): Command
    {
        return new \Propel\Generator\Command\SqlBuildCommand();
    }

    /**
     * @param InputInterface $input
     * @return array
     */
    protected function getSubCommandArguments(InputInterface $input): array
    {
        $config = $this->getConfig();
        $defaultSqlDir = $config['paths']['sqlDir'];

        return [
            '--connection' => $this->getConnections($input->getOption('connection')),
            '--output-dir' => $input->getOption('sql-dir') ?: $defaultSqlDir,
            '--overwrite' => $input->getOption('overwrite')
        ];
    }
}
