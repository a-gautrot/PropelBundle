<?php

namespace Propel\Bundle\PropelBundle\Tests\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Propel\Bundle\PropelBundle\Command\DatabaseCreateCommand;
use Propel\Bundle\PropelBundle\Command\DatabaseDropCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * # OracleDatabaseCommandTest
 */
class OracleDatabaseCommandTest extends TestCase
{
    /**
     * @return iterable
     */
    public static function commands(): iterable
    {
        yield [DatabaseCreateCommand::class, []];
        yield [DatabaseDropCommand::class, ['--force' => true]];
    }

    /**
     * @param string $class
     * @param array $arguments
     * @return void
     * @throws Exception
     * @dataProvider commands
     */
    #[DataProvider('commands')]
    public function testOracleAdministrationFailsClearlyWithoutOpeningAConnection(string $class, array $arguments): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('propel.configuration', ['database' => ['connections' => ['oracle_test' => [
            'adapter' => 'oracle', 'dsn' => 'oci:dbname=//invalid-host/FREEPDB1',
        ]]]]);
        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('getCacheDir')->willReturn(sys_get_temp_dir());
        $kernel->method('getEnvironment')->willReturn('test');
        $command = new $class($container);
        $command->setApplication(new Application($kernel));
        $tester = new CommandTester($command);
        self::assertSame(1, $tester->execute($arguments));
        self::assertStringContainsString('Oracle databases must be', $tester->getDisplay());
    }
}
