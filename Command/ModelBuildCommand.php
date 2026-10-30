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
use Propel\Generator\Command\ModelBuildCommand as BaseModelBuildCommand;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * # ModelBuildCommand
 * @author Kévin Gomez <contact@kevingomez.fr>
 * @api
 */
class ModelBuildCommand extends WrappedCommand
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
            ->setName('propel:model:build')
            ->setDescription('Build the model classes based on Propel XML schemas')
            ->addArgument(
                'bundle',
                InputArgument::OPTIONAL,
                'The bundle to generate model classes from.'
            )
            ->addOption(
                'connection',
                mode: InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                description: 'Connection to use. Example: default, bookstore.'
            )
            ->addOption(
                'loader-script-dir',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Target folder of the database table map loader script. Defaults to paths.loaderScriptDir.'
            );
    }

    /**
     * @return Command
     * @throws LogicException
     */
    #[Override]
    protected function createSubCommandInstance(): Command
    {
        return new BaseModelBuildCommand();
    }

    /**
     * @param InputInterface $input
     * @return array<string, string>
     * @throws RuntimeException
     */
    #[Override]
    protected function getSubCommandArguments(InputInterface $input): array
    {
        $outputDir = "{$this->getKernel()->getProjectDir()}/";

        return ['--output-dir' => $outputDir];
    }
}
