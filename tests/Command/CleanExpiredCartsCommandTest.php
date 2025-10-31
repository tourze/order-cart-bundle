<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\OrderCartBundle\Command\CleanExpiredCartsCommand;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(CleanExpiredCartsCommand::class)]
#[RunTestsInSeparateProcesses]
final class CleanExpiredCartsCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    public function testExecuteCommand(): void
    {
        $exitCode = $this->commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertTrue(
            str_contains($output, 'Successfully deleted') || str_contains($output, 'No expired cart items found'),
            'Command should show either success message or no items found message'
        );
    }

    public function testCommandConfiguration(): void
    {
        $command = self::getContainer()->get(CleanExpiredCartsCommand::class);
        $this->assertInstanceOf(CleanExpiredCartsCommand::class, $command);
        $this->assertSame('order-cart:clean-expired', $command->getName());
        $this->assertStringContainsString('Clean expired cart items', $command->getDescription());
    }

    public function testOptionDays(): void
    {
        $exitCode = $this->commandTester->execute(['--days' => '7']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Removing cart items older than', $output);
    }

    public function testOptionDryRun(): void
    {
        $exitCode = $this->commandTester->execute(['--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('dry-run mode', $output);
    }

    public function testOptionBatchSize(): void
    {
        $exitCode = $this->commandTester->execute(['--batch-size' => '50']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        // 批处理大小通常不会在输出中显示，但命令应该成功执行
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        // Get command from service container
        $command = self::getContainer()->get(CleanExpiredCartsCommand::class);
        $this->assertInstanceOf(CleanExpiredCartsCommand::class, $command);

        $application = new Application();
        $application->add($command);

        $command = $application->find('order-cart:clean-expired');
        $this->commandTester = new CommandTester($command);
    }
}
