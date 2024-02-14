<?php

declare(strict_types=1);

namespace Vrok\MonitoringBundle\Tests\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mailer\MailerInterface;
use Vrok\MonitoringBundle\Command\SendAliveMessageCommand;

class SendAliveMessageCommandTest extends KernelTestCase
{
    public function testSendsMailAndCreatesLog(): void
    {
        $logger = $this->createStub(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('Sent alive-message for mail@address.com to the mail transport or message bus');

        $mailer = $this->createStub(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send');

        $command = new SendAliveMessageCommand(
            'mail@address.com',
            'test app',
            $mailer,
            $logger,
        );
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(
            SendAliveMessageCommand::SUCCESS,
            $commandTester->getStatusCode()
        );
    }

    public function testService(): void
    {
        $application = new Application(static::bootKernel());
        self::assertTrue($application->has('monitor:send-alive-message'));
    }
}
