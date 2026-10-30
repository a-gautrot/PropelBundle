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
use Propel\Generator\Command\SqlBuildCommand as PropelSqlBuildCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

/**
 * # SqlBuildCommand
 * @author Kévin Gomez <contact@kevingomez.fr>
 * @api
 */
#[AsCommand(name: 'propel:sql:build', description: 'Build SQL files')]
class SqlBuildCommand extends WrappedCommand
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
            ->addOption('sql-dir', mode: InputOption::VALUE_REQUIRED, description: 'The SQL files directory')
            ->addOption('overwrite', mode: InputOption::VALUE_NONE, description: '')
            ->addOption('connection', mode: InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, description: 'Connection to use. Example: default, bookstore');
    }

    /**
     * @return Command
     * @throws LogicException
     */
    #[Override]
    protected function createSubCommandInstance(): Command
    {
        return new PropelSqlBuildCommand();
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
            '--output-dir' => $input->getOption('sql-dir') ?: $defaultSqlDir,
            '--overwrite' => $input->getOption('overwrite')
        ];
    }
}
