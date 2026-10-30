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
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * # FixturesDumpCommand
 *
 * @author William DURAND <william.durand1@gmail.com>
 * @api
 */
class FixturesDumpCommand extends AbstractCommand
{
    /**
     * Default fixtures directory.
     */
    protected string $defaultFixturesDir = 'propel/fixtures';

    /**
     * @return void
     * @throws InvalidArgumentException
     * @see \Symfony\Component\Console\Command\Command
     */
    #[Override]
    protected function configure(): void
    {
        $this
            ->setName('propel:fixtures:dump')
            ->setDescription('Dump data from database into YAML fixtures file.')
            ->setHelp(<<<EOT
The <info>propel:fixtures:dump</info> dumps data from database into YAML fixtures file.

  <info>php app/console propel:fixtures:dump</info>

The <info>--connection</info> parameter allows you to change the connection to use.
The <info>--dir</info> parameter allows you to change the output directory.
The default connection is the active connection (propel.dbal.default_connection).
EOT
            )
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a connection to use')
            ->addOption('dir', null, InputOption::VALUE_OPTIONAL, 'Set this parameter to define a fixture directory');
    }

    /**
     * {@inheritdoc}
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \RuntimeException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws ServiceCircularReferenceException
     * @throws ServiceNotFoundException
     * @throws IOException
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fixtureDir = $input->getOption('dir') ?: $this->defaultFixturesDir;
        $path = $this->getKernel()->getProjectDir() . '/' . $fixtureDir;

        if (!file_exists($path)) {
            $output->writeln("<info>The $path folder does not exists.</info>");
            if ($this->askConfirmation($input, $output, "<question>Do you want me to create it for you ?</question> [Yes]")) {
                $fs = new Filesystem();
                $fs->mkdir($path);
                $this->writeNewDirectory($output, $path);
            } else {
                throw new IOException("Unable to find the $path folder");
            }
        }

        $filename = "$path/fixtures_" . time() . '.yml';
        $dumper = $this->getContainer()->get('propel.dumper.yaml');

        $dumper->dump($filename, $input->getOption('connection'));

        $this->writeNewFile($output, $filename);

        return \Propel\Generator\Command\AbstractCommand::CODE_SUCCESS;
    }
}
