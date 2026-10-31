<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Command;

use InvalidArgumentException;
use JsonException;
use LogicException;
use Override;
use Propel\Bundle\PropelBundle\Service\SchemaLocator;
use ReflectionClass;
use RuntimeException;
use SplFileInfo;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Application as SymfonyConsoleApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException as ConsoleInvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException as ConsoleLogicException;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * # AbstractCommand
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
abstract class AbstractCommand extends Command
{
    /**
     * @var string|null
     */
    protected ?string $cacheDir = null;
    /**
     * @var BundleInterface|null
     */
    protected ?BundleInterface $bundle = null;
    /**
     * @var InputInterface
     * @psalm-suppress PropertyNotSetInConstructor Initialized by Symfony in initialize().
     */
    protected InputInterface $input;
    /**
     * @var OutputInterface
     * @psalm-suppress PropertyNotSetInConstructor Initialized by Symfony in initialize().
     */
    protected OutputInterface $output;

    use FormattingHelpers;

    /**
     * @param ContainerInterface $container
     * @param string|null $name
     * @throws ConsoleLogicException
     */
    public function __construct(private ContainerInterface $container, ?string $name = null)
    {
        parent::__construct($name);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws RuntimeException
     * @throws ConsoleInvalidArgumentException
     * @throws ServiceCircularReferenceException
     * @throws ServiceNotFoundException
     */
    #[Override]
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->output = $output;
        $this->cacheDir = "{$this->getKernel()->getCacheDir()}/propel";

        if ($input->hasArgument('bundle')
            && !empty($input->getArgument('bundle'))
            && str_starts_with($input->getArgument('bundle'), '@')) {
            $this->bundle = $this
                ->getContainer()
                ->get('kernel')
                ->getBundle(substr($input->getArgument('bundle'), 1));
        }
    }

    /**
     * Return the current Propel cache directory.
     *
     * @return string The current Propel cache directory.
     * @throws RuntimeException
     */
    protected function getCacheDir(): string
    {
        if ($this->cacheDir === null) {
            throw new RuntimeException('The command has not been initialized.');
        }

        return $this->cacheDir;
    }

    /**
     * @return KernelInterface
     * @throws RuntimeException
     */
    protected function getKernel(): KernelInterface
    {
        /** @var Application $application */
        $application = $this->getRequiredApplication();

        return $application->getKernel();
    }

    /**
     * @return SymfonyConsoleApplication
     * @throws RuntimeException
     */
    protected function getRequiredApplication(): SymfonyConsoleApplication
    {
        $application = $this->getApplication();
        if ($application === null) {
            throw new RuntimeException('The command is not attached to an application.');
        }

        return $application;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @return HelperSet
     * @throws RuntimeException
     */
    protected function getRequiredHelperSet(): HelperSet
    {
        $helpers = $this->getHelperSet();
        if ($helpers === null) {
            throw new RuntimeException('The command has no helper set.');
        }

        return $helpers;
    }

    /**
     * Create all the files needed by Propel's commands.
     * @return void
     * @throws JsonException
     * @throws LogicException
     * @throws RuntimeException
     * @throws ParameterNotFoundException
     * @throws ServiceNotFoundException
     * @throws IOException
     * @throws DirectoryNotFoundException
     */
    protected function setupBuildTimeFiles(): void
    {
        $fs = new Filesystem();
        $fs->mkdir($this->getCacheDir());

        // collect all schemas
        $this->copySchemas($this->getKernel(), $this->getCacheDir());

        // propel.json
        $this->createPropelConfigurationFile("$this->cacheDir/propel.json");
    }

    /**
     * @param KernelInterface $kernel The application kernel.
     * @param string $cacheDir The directory in which the schemas will
     *                                  be copied.
     * @return void
     * @throws LogicException
     * @throws RuntimeException
     * @throws ServiceNotFoundException
     * @throws DirectoryNotFoundException
     */
    protected function copySchemas(KernelInterface $kernel, string $cacheDir): void
    {
        $filesystem = new Filesystem();

        $finalSchemas = $this->getFinalSchemas($kernel, $this->bundle);
        foreach ($finalSchemas as $schema) {
            [$bundle, $finalSchema] = $schema;

            if ($bundle) {
                $file = $cacheDir . DIRECTORY_SEPARATOR . 'bundle-'
                    . $bundle->getName() . '-' . $finalSchema->getBaseName();
            } else {
                $file = $cacheDir . DIRECTORY_SEPARATOR . 'app-' . $finalSchema->getBaseName();
            }

            $filesystem->copy((string)$finalSchema, $file, overwriteNewerFiles: true);

            // the package needs to be set absolute
            // besides, the automated namespace to package conversion has
            // not taken place yet so it needs to be done manually
            $database = simplexml_load_file($file);
            if ($database === false) {
                throw new RuntimeException("Cannot load schema: $file");
            }

            if (isset($database['package'])) {
                // Do not use the prefix!
                // This is used to override the package resulting from namespace conversion.
                $database['package'] = $database['package'];
            } elseif (isset($database['namespace'])) {
                if ($bundle) {
                    $database['package'] = $this->getPackageFromBundle($bundle, (string)$database['namespace']);
                } else {
                    $database['package'] = $this->getPackageFromApp((string)$database['namespace']);
                }
            } else {
                $bundleName = $bundle ? $bundle->getName() : 'App';
                throw new RuntimeException(
                    "$bundleName : Please define a `package` attribute or a `namespace` attribute for schema `"
                    . $finalSchema->getBaseName() . '`'
                );
            }

            if ($this->input->hasOption('connection')) {
                $connections = $this->input->getOption('connection') ?: [$this->getDefaultConnection()];

                if (!in_array((string)$database['name'], $connections)) {
                    // we skip this schema because the connection name doesn't
                    // match the input values
                    $filesystem->remove($file);
                    $connectionNames = implode(',', $connections);
                    $this->output->writeln(
                        "<info>Skipped schema {$finalSchema->getPathname()} due to database name missmatch "
                        . "({$database['name']} not in [$connectionNames]).</info>"
                    );
                    continue;
                }
            }

            foreach ($database->table ?? [] as $table) {
                if (isset($table['package'])) {
                    $table['package'] = $table['package'];
                } elseif (isset($table['namespace'])) {
                    if ($bundle) {
                        $table['package'] = $this->getPackageFromBundle($bundle, (string)$table['namespace']);
                    } else {
                        $table['package'] = $this->getPackageFromApp((string)$table['namespace']);
                    }
                } elseif (isset($database['package'])) {
                    $table['package'] = $database['package'];
                }
            }

            file_put_contents($file, $database->asXML());
        }
    }

    /**
     * Return a list of final schema files that will be processed.
     *
     * @param KernelInterface $kernel The application kernel.
     * @param BundleInterface|null $bundle If given, only the bundle's schemas will
     *                                     be returned.
     *
     * @return array<string, array{?BundleInterface, SplFileInfo}> A list of schemas.
     * @throws LogicException
     * @throws RuntimeException
     * @throws ServiceNotFoundException
     * @throws DirectoryNotFoundException
     */
    protected function getFinalSchemas(KernelInterface $kernel, ?BundleInterface $bundle = null): array
    {
        if ($bundle !== null) {
            return $this->getSchemaLocator()->locateFromBundle($bundle);
        }

        return $this->getSchemaLocator()->locateFromBundlesAndConfiguration($kernel->getBundles());
    }

    /**
     * @return SchemaLocator
     * @throws RuntimeException
     * @throws ServiceNotFoundException
     */
    protected function getSchemaLocator(): SchemaLocator
    {
        $locator = $this->getContainer()->get('propel.schema_locator');
        if (!$locator instanceof SchemaLocator) {
            throw new RuntimeException('The propel.schema_locator service must be a SchemaLocator.');
        }

        return $locator;
    }

    /**
     * @param BundleInterface $bundle
     * @param string $namespace
     *
     * @return string
     * @throws RuntimeException
     */
    protected function getPackageFromBundle(BundleInterface $bundle, string $namespace): string
    {
        //find relative path from namespace to bundle->getNamespace()
        $baseNamespace = (new ReflectionClass($bundle))->getNamespaceName();
        if (str_starts_with($namespace, $baseNamespace)) {
            //base namespace fits
            //eg.
            //  Base: Jarves/JarvesBundle => Jarves
            //  Model namespace: Jarves\Model
            //  strpos(Jarves\Model, Jarves) === 0
            // $namespaceDiff = Model

            $namespaceDiff = substr($namespace, strlen($baseNamespace) + 1);

            $bundleRoot = realpath($bundle->getPath());
            if ($bundleRoot === false) {
                throw new RuntimeException('The bundle directory does not exist.');
            }
            $bundlePath = $bundleRoot . '/' . str_replace('\\', '/', $namespaceDiff);
            $appPath = realpath($this->getKernel()->getProjectDir());
            if ($appPath === false) {
                throw new RuntimeException('The project directory does not exist.');
            }

            $path = static::getRelativePath($bundlePath, $appPath);

            return str_replace('/', '.', $path);
        }

        //does not match, or it's an absolute path, so return it without suffix
        if ($namespace[0] === '\\') {
            $namespace = substr($namespace, 1);
        }

        return str_replace('\\', '.', $namespace);
    }

    /**
     * Returns a relative path from $path to $current.
     *
     * @param string $from
     * @param string $to relative to this
     *
     * @return string relative path without trailing slash
     */
    public static function getRelativePath(string $from, string $to): string
    {
        $from = '/' . trim($from, '/');
        $to = '/' . trim($to, '/');

        if (str_starts_with($from, $to)) {
            return substr($from, strlen($to) + ($to === '/' ? 0 : 1));
        }

        $result = '';
        while ($to && !str_contains($from, $to)) {
            $result .= '../';
            $position = strrpos($to, '/');
            $to = $position === false ? '' : substr($to, 0, $position);
        }

        return !$to /*we reached root*/ ? $result . substr($from, 1) : $result . substr($from, strlen($to) + 1);
    }

    /**
     * @param string $namespace
     *
     * @return string
     */
    protected function getPackageFromApp(string $namespace): string
    {
        if ($namespace[0] === '\\') {
            $namespace = substr($namespace, 1);
        }

        if (stripos($namespace, 'App\\') === 0) {
            $namespace = substr($namespace, 4);
        }

        return 'src.' . str_replace('\\', '.', $namespace);
    }

    /**
     * Returns the name of the default connection.
     *
     * @return string
     * @throws RuntimeException
     * @throws ParameterNotFoundException
     */
    protected function getDefaultConnection(): string
    {
        $config = $this->getConfig();

        $connection = $config['generator']['defaultConnection'] ?? key($config['database']['connections']);
        if (!is_string($connection) || $connection === '') {
            throw new RuntimeException('No default Propel connection is configured.');
        }

        return $connection;
    }

    /**
     * @return array{
     *     paths: array{
     *         schemaDir: string, sqlDir: string, migrationDir: string,
     *         composerDir: ?string, loaderScriptDir: string
     *     },
     *     database: array{connections: array<string, array<string, mixed>>},
     *     runtime: array{defaultConnection: string, logging: bool},
     *     migrations: array{tableName: string, parserClass: string},
     *     generator: array<string, mixed>
     * }
     * @throws ParameterNotFoundException
     */
    protected function getConfig(): array
    {
        /**
         * @var array{
         *     paths: array{
         *         schemaDir: string, sqlDir: string, migrationDir: string,
         *         composerDir: string|null, loaderScriptDir: string
         *     },
         *     database: array{connections: array<string, array<string, mixed>>},
         *     runtime: array{defaultConnection: string, logging: bool},
         *     migrations: array{tableName: string, parserClass: string},
         *     generator: array<string, mixed>
         * }
         */
        return $this->getContainer()->getParameter('propel.configuration');
    }

    /**
     * Create a JSON file which represents propel.configuration.
     *
     * @param string $file Should be 'propel.json'.
     * @return void
     * @throws JsonException
     * @throws ParameterNotFoundException
     */
    protected function createPropelConfigurationFile(string $file): void
    {
        $propelConfig = $this->getConfig();

        //needed because Propel 2's configuration tree is a bit different
        //propel.runtime.logging is PropelBundle feature only.
        unset($propelConfig['runtime']['logging']);

        $config = ['propel' => $propelConfig];

        file_put_contents($file, json_encode($config, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }

    /**
     * Run a Symfony command.
     *
     * @param Command $command The command to run.
     * @param array<string, mixed> $parameters An array of parameters to give to the command.
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return int The command return code.
     *
     * @throws ExceptionInterface
     * @throws RuntimeException
     */
    protected function runCommand(
        Command         $command,
        array           $parameters,
        InputInterface  $input,
        OutputInterface $output
    ): int
    {
        // add the command's name to the parameters
        array_unshift($parameters, $this->getName());

        // merge the default parameters
        $extraParameters = ['--verbose' => $input->getOption('verbose')];

        if ($command->getDefinition()->hasOption('schema-dir')) {
            $extraParameters['--schema-dir'] = $this->cacheDir;
        }

        if ($command->getDefinition()->hasOption('config-dir')) {
            $extraParameters['--config-dir'] = $this->cacheDir;
        }

        if ($input->hasOption('loader-script-dir') && !empty($input->getOption('loader-script-dir'))) {
            $parameters['--loader-script-dir'] = $input->getOption('loader-script-dir');
        }

        $parameters = array_merge($extraParameters, $parameters);

        if ($input->hasOption('platform')) {
            $platform = $input->getOption('platform') ?: $this->getPlatform();
            if ($platform !== null && $platform !== '') {
                $parameters['--platform'] = $platform;
            }
        }

        $command->setApplication($this->getRequiredApplication());

        // and run the sub-command
        return $command->run(new ArrayInput($parameters), $output);
    }

    /**
     * Reads the platform class from the configuration
     *
     * @return string|null The platform class name.
     * @throws ParameterNotFoundException
     */
    protected function getPlatform(): ?string
    {
        $config = $this->getConfig();

        return $config['generator']['platformClass'];
    }

    /**
     * Translates a list of connection names to their DSN equivalents.
     *
     * @param string[] $connections The names.
     *
     * @return string[]
     */
    protected function getConnections(array $connections): array
    {
        $dsnList = [];
        foreach ($connections as $connection) {
            $dsnList[] = "$connection={$this->getDsn($connection)}";
        }

        return $dsnList;
    }

    /**
     * Get the DSN for a given connection.
     *
     * @param string $connectionName The connection name.
     *
     * @return string The DSN.
     * @throws InvalidArgumentException
     */
    protected function getDsn(string $connectionName): string
    {
        $connection = $this->getConnectionData($connectionName);
        // Add user and password to dsn string
        $dsn = explode(';', $connection['dsn']);
        if (isset($connection['user'])) {
            $dsn[] = 'user=' . urlencode($connection['user']);
        }
        if (isset($connection['password'])) {
            $dsn[] = 'password=' . urlencode($connection['password']);
        }

        return implode(';', $dsn);
    }

    /**
     * Get the data (host, user, ...) for a given connection.
     *
     * @param string $name The connection name.
     *
     * @return array<string, mixed> The connection data.
     * @throws InvalidArgumentException
     */
    protected function getConnectionData(string $name): array
    {
        $knownConnections = $this->getConfig();
        if (!isset($knownConnections['database']['connections'][$name])) {
            throw new InvalidArgumentException("Unknown connection \"$name\"");
        }

        return $knownConnections['database']['connections'][$name];
    }

    /**
     * Extract the database name from a given DSN
     *
     * @param string $dsn A DSN
     * @return ?string The database name extracted from the given DSN
     */
    protected function parseDbName(string $dsn): ?string
    {
        preg_match('#(dbname|Database)=(\w+)#', $dsn, $matches);

        // e.g. SQLite
        return $matches[2] ?? null;
    }

    /**
     * Returns the name of the migrations table.
     *
     * @return string
     * @throws ParameterNotFoundException
     */
    protected function getMigrationsTable(): string
    {
        $config = $this->getConfig();

        return $config['migrations']['tableName'];
    }
}
