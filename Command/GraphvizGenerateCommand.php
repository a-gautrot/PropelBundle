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
    #[Override]
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('propel:graphviz:generate')
            ->setDescription('Generate Graphviz files (.dot).')
            ->addOption(
                'output-dir',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The output directory.',
                default: BaseGraphvizGenerateCommand::DEFAULT_OUTPUT_DIRECTORY
            );
    }

    /**
     * @return Command
     * @throws LogicException
     */
    #[Override]
    protected function createSubCommandInstance(): Command
    {
        return new BaseGraphvizGenerateCommand();
    }

    /**
     * @param InputInterface $input
     * @return array<string, mixed>
     * @throws InvalidArgumentException
     */
    #[Override]
    protected function getSubCommandArguments(InputInterface $input): array
    {
        return [
            '--output-dir' => $input->getOption('output-dir'),
        ];
    }
}
