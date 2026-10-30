<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Command;

use App\AppBundle;
use InvalidArgumentException as BaseInvalidArgumentException;
use JsonException;
use LogicException as BaseLogicException;
use Override;
use Propel\Bundle\PropelBundle\Form\FormBuilder;
use Propel\Generator\Command\AbstractCommand as PropelAbstractCommand;
use Propel\Generator\Config\GeneratorConfig;
use Propel\Generator\Manager\ModelManager;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Table;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * # FormGenerateCommand
 * @author William DURAND <william.durand1@gmail.com>
 * @api
 */
class FormGenerateCommand extends AbstractCommand
{
    /**
     * @var string
     */
    const DEFAULT_FORM_TYPE_DIRECTORY = '/Form/Type';

    use BundleTrait;

    /**
     * @return void
     * @throws InvalidArgumentException
     */
    #[Override]
    protected function configure(): void
    {
        $this
            ->setName('propel:form:generate')
            ->setDescription('Generate Form types stubs based on the schema.xml.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command allows you to quickly generate Form Type stubs for a given bundle.

<info>php app/console %command.full_name%</info>

The <info>--force</info> parameter allows you to overwrite existing files.
EOT
            )
            ->addArgument(
                'bundle',
                InputArgument::OPTIONAL,
                'The bundle to use to generate Form types (Ex: @AcmeDemoBundle).'
            )
            ->addArgument(
                'models',
                InputArgument::IS_ARRAY,
                'Model classes to generate Form Types from.'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Overwrite existing Form types.'
            )
            ->addOption(
                'platform',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The platform.'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws BaseInvalidArgumentException
     * @throws JsonException
     * @throws BaseLogicException
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $kernel = $this->getKernel();
        $models = $input->getArgument('models');
        $force = $input->getOption('force');

        $bundle = $this->getBundle($input, $output);

        $this->setupBuildTimeFiles();
        $schemas = $this->getFinalSchemas($kernel, $bundle);

        if (!$schemas) {
            $output->writeln("No <comment>*schemas.xml</comment> files found in bundle <comment>{$bundle->getName()}</comment>.");

            return PropelAbstractCommand::CODE_ERROR;
        }

        $manager = $this->getModelManager($input, $schemas);

        foreach ($manager->getDataModels() as $dataModel) {
            foreach ($dataModel->getDatabases() as $database) {
                $this->createFormTypeFromDatabase($bundle, $database, $models, $output, $force);
            }
        }

        return PropelAbstractCommand::CODE_SUCCESS;
    }

    /**
     * Get the ModelManager to use.
     *
     * @param InputInterface $input An InputInterface instance.
     * @param array<string, array{?BundleInterface, SplFileInfo}> $schemas A list of schemas.
     *
     * @return ModelManager
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    protected function getModelManager(InputInterface $input, array $schemas): ModelManager
    {
        $schemaFiles = [];
        foreach ($schemas as $data) {
            $schemaFiles[] = $data[1];
        }

        $manager = new ModelManager();
        $manager->setFilesystem(new Filesystem());
        $manager->setGeneratorConfig($this->getGeneratorConfig($input));
        $manager->setSchemas($schemaFiles);

        return $manager;
    }

    /**
     * Get the GeneratorConfig instance to use.
     *
     * @param InputInterface $input An InputInterface instance.
     *
     * @return GeneratorConfig
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    protected function getGeneratorConfig(InputInterface $input): GeneratorConfig
    {
        $generatorConfig = null;

        if ($input->getOption('platform') !== null) {
            $generatorConfig = ['propel' => ['generator' => ['platformClass' => $input->getOption('platform')]]];
        }

        return new GeneratorConfig("{$this->getCacheDir()}/propel.json", $generatorConfig);
    }

    /**
     * Create FormTypes from a given database, bundle and models.
     *
     * @param BundleInterface $bundle The bundle for which the FormTypes will be generated.
     * @param Database $database The database to inspect.
     * @param string[] $models The models to build.
     * @param OutputInterface $output An OutputInterface instance
     * @param boolean $force Override files if present.
     * @return void
     * @throws IOException
     */
    protected function createFormTypeFromDatabase(BundleInterface $bundle, Database $database, array $models, OutputInterface $output, bool $force = false): void
    {
        $dir = $this->createDirectory($bundle, $output);

        foreach ($database->getTables() as $table) {
            if (!empty($models) && !in_array($table->getPhpName(), $models)) {
                continue;
            }

            $file = new SplFileInfo("$dir/{$table->getPhpName()}Type.php");

            if (!file_exists($file->getPathname()) || $force === true) {
                $this->writeFormType($bundle, $table, $file, $force, $output);
            } else {
                $output->writeln(sprintf('File <comment>%-60s</comment> exists, skipped. Try the <info>--force</info> option.', $this->getRelativeFileName($file)));
            }
        }
    }

    /**
     * Create the FormType directory and log the result.
     *
     * @param BundleInterface $bundle The bundle in which we'll create the directory.
     * @param OutputInterface $output An OutputInterface instance.
     *
     * @return string The path to the created directory.
     * @throws IOException
     */
    protected function createDirectory(BundleInterface $bundle, OutputInterface $output): string
    {
        $fs = new Filesystem();

        if ($bundle->getName() == AppBundle::NAME) {
            $dir = $bundle->getPath() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Form';
        } else {
            $dir = $bundle->getPath() . self::DEFAULT_FORM_TYPE_DIRECTORY;
        }

        if (!$fs->exists($dir)) {
            $fs->mkdir($dir);
            $this->writeNewDirectory($output, $dir);
        }

        return $dir;
    }

    /**
     * Write a FormType.
     *
     * @param BundleInterface $bundle The bundle in which the FormType will be created.
     * @param Table $table The table for which the FormType will be created.
     * @param SplFileInfo $file File representing the FormType.
     * @param boolean $force Is the write forced?
     * @param OutputInterface $output An OutputInterface instance.
     * @return void
     * @throws RuntimeException
     * @throws ParameterNotFoundException
     */
    protected function writeFormType(BundleInterface $bundle, Table $table, SplFileInfo $file, bool $force, OutputInterface $output): void
    {
        $formBuilder = new FormBuilder();
        $formTypeContent = $formBuilder->buildFormType($bundle, $table, self::DEFAULT_FORM_TYPE_DIRECTORY);

        file_put_contents($file->getPathName(), $formTypeContent);
        $this->writeNewFile($output, $this->getRelativeFileName($file) . ($force ? ' (forced)' : ''));
    }

    /**
     * @param SplFileInfo $file
     * @return string
     * @throws RuntimeException
     * @throws ParameterNotFoundException
     */
    protected function getRelativeFileName(SplFileInfo $file): string
    {
        $projectDir = $this->getContainer()->getParameter('kernel.project_dir');
        if (!is_string($projectDir)) {
            throw new RuntimeException('The kernel.project_dir parameter must be a string.');
        }
        $parentDir = realpath("$projectDir/../");
        if ($parentDir === false) {
            throw new RuntimeException('The parent project directory does not exist.');
        }

        return substr(str_replace($parentDir, '', $file->getPathname()), 1);
    }
}
