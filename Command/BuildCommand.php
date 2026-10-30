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
use Propel\Generator\Command\AbstractCommand as PropelAbstractCommand;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * # BuildCommand
 * @author Kévin Gomez <contact@kevingomez.fr>
 * @api
 */
#[\Symfony\Component\Console\Attribute\AsCommand(name: 'propel:build', description: 'Hub for Propel build commands (Model classes, SQL)')]
class BuildCommand extends Command
{
    /**
     * @return void
     * @throws InvalidArgumentException
     */
    #[Override]
    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputOption('classes', mode: InputOption::VALUE_NONE, description: 'Build only classes'),
                new InputOption('sql', mode: InputOption::VALUE_NONE, description: 'Build only SQL'),
                new InputOption('insert-sql', mode: InputOption::VALUE_NONE, description: 'Build all and insert SQL'),
                new InputOption('connection', mode: InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, description: 'Connection to use. Example: default, bookstore')
            ]);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws ExceptionInterface
     * @throws RuntimeException
     * @see Command
     *
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $application = $this->getApplication();
        if ($application === null) {
            throw new RuntimeException('The command is not attached to an application.');
        }
        if (!$input->getOption('sql')) {
            $in = new ArrayInput([
                'command' => 'propel:model:build',
                '--connection' => $input->getOption('connection'),
                '--verbose' => $input->getOption('verbose')
            ]);
            $cmd = $application->find('propel:model:build');
            $cmd->run($in, $output);
        }

        if (!$input->getOption('classes')) {
            $in = new ArrayInput([
                'command' => 'propel:build:sql',
                '--connection' => $input->getOption('connection'),
                '--verbose' => $input->getOption('verbose'),
            ]);
            $cmd = $application->find('propel:sql:build');
            $cmd->run($in, $output);
        }

        if ($input->getOption('insert-sql')) {
            $in = new ArrayInput([
                'command' => 'propel:sql:insert',
                '--connection' => $input->getOption('connection'),
                '--force' => true,
                '--verbose' => $input->getOption('verbose'),
            ]);
            $cmd = $application->find('propel:sql:insert');
            $cmd->run($in, $output);
        }

        return PropelAbstractCommand::CODE_SUCCESS;
    }
}
