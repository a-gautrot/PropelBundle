<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Command;

use Propel\Generator\Command\GraphvizGenerateCommand as BaseGraphvizGenerateCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * # GraphvizGenerateCommand
 * @author Kévin Gomez <contact@kevingomez.fr>
 * @api
 */
class GraphvizGenerateCommand extends WrappedCommand
{
    /**
     * @return void
     * @throws InvalidArgumentException
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('propel:graphviz:generate')
            ->setDescription('Generate Graphviz files (.dot)')
            ->addOption('output-dir', null, InputOption::VALUE_REQUIRED, 'The output directory', BaseGraphvizGenerateCommand::DEFAULT_OUTPUT_DIRECTORY);
    }

    /**
     * @return Command
     * @throws LogicException
     */
    protected function createSubCommandInstance(): Command
    {
        return new BaseGraphvizGenerateCommand();
    }

    /**
     * @param InputInterface $input
     * @return array<string, mixed>
     * @throws InvalidArgumentException
     */
    protected function getSubCommandArguments(InputInterface $input): array
    {
        return [
            '--output-dir' => $input->getOption('output-dir'),
        ];
    }
}
