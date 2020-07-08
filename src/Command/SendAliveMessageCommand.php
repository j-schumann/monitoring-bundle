<?php

declare(strict_types=1);

namespace Vrok\MonitoringBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

class SendAliveMessageCommand
    extends Command
    implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;

    protected string $monitorAddress;
    protected string $appName;

    public function __construct(string $monitorAddress, string $appName)
    {
        parent::__construct();
        $this->monitorAddress = $monitorAddress;
        $this->appName = $appName;
    }

    protected function configure()
    {
        $this
            ->setName('monitor:send-alive-message')
            ->setDescription('Sends an email to the configured monitor address with a notification that the app is alive and can send emails.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $subject = "Service {$this->appName} is alive!";
        $body = "Automatic message from {$this->appName}: "
            . "The service is alive and can send emails"
            . "\nat ".date(DATE_ATOM)
            // we need a comparable integer for the --capture-max option of the
            // nagios/icinga plugin "check_imap_receive"
            ." (timestamp ".time().")"
        ;

        $email = (new Email())
            // FROM must be added via listener
            ->to($this->monitorAddress)
            ->subject($subject)
            ->text($body);

        // only if the message is sent directly via a Transport an instance of
        // SentMessage is returned. So we cannot check here if the email was
        // really sent, it probably is first sent to the message queue to be
        // processed asynchronously by a worker
        $this->mailer()->send($email);

        $success = "Sent alive-message for {$this->monitorAddress} to the mail transport or message bus";
        $this->logger()->info($success);
        $output->writeln($success);

        return 0;
    }

    private function logger(): LoggerInterface
    {
        return $this->container->get(__METHOD__);
    }

    private function mailer(): MailerInterface
    {
        return $this->container->get(__METHOD__);
    }
}
