<?php

namespace Propel\Bundle\PropelBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Propel\Bundle\PropelBundle\Command\FixturesLoadCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * # FixturesLoadCommandTest
 */
class FixturesLoadCommandTest extends TestCase
{
    /**
     * @return void
     */
    public function testMissingDirectoryReturnsAnError(): void
    {
        $tester = $this->createCommandTester();

        $status = $tester->execute(['--dir' => 'Tests/Command/nonexistent-fixtures']);

        self::assertSame(1, $status);
        self::assertStringContainsString('nonexistent-fixtures', $tester->getDisplay());
        self::assertStringContainsString('does not exist', $tester->getDisplay());
    }

    /**
     * @return CommandTester
     */
    private function createCommandTester(): CommandTester
    {
        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn(dirname(__DIR__, 2));
        $kernel->method('getCacheDir')->willReturn(sys_get_temp_dir());
        $command = new FixturesLoadCommand(new ContainerBuilder());
        $command->setApplication(new Application($kernel));

        return new CommandTester($command);
    }

    /**
     * @return void
     */
    public function testFileCannotBeUsedAsFixturesDirectory(): void
    {
        $tester = $this->createCommandTester();

        $status = $tester->execute(['--dir' => 'Tests/Command/FixturesLoadCommandTest.php']);

        self::assertSame(1, $status);
        self::assertStringContainsString('does not exist', $tester->getDisplay());
    }
}
