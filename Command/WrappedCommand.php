<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Command;

use JsonException;
use LogicException;
use Override;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

/**
 * # WrappedCommand
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
abstract class WrappedCommand extends AbstractCommand
{
    /**
     * @return void
     * @throws InvalidArgumentException
     */
    #[Override]
    protected function configure(): void
    {
        $this
            ->addOption('platform', null, InputOption::VALUE_OPTIONAL, 'The platform');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     *
     * @throws ExceptionInterface
     * @throws JsonException
     * @throws LogicException
     * @throws RuntimeException
     * @throws ParameterNotFoundException
     * @throws ServiceNotFoundException
     * @throws IOException
     * @throws DirectoryNotFoundException
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $params = $this->getSubCommandArguments($input);
        $command = $this->createSubCommandInstance();

        $this->setupBuildTimeFiles();

        return $this->runCommand($command, $params, $input, $output);
    }

    /**
     * Returns all the arguments and options needed by the Propel sub-command.
     *
     * @param InputInterface $input
     * @return array<string, mixed>
     */
    abstract protected function getSubCommandArguments(InputInterface $input): array;

    /**
     * Creates the instance of the Propel sub-command to execute.
     *
     * @return Command
     */
    abstract protected function createSubCommandInstance(): Command;
}
