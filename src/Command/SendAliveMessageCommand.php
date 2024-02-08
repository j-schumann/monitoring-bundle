<?php

declare(strict_types=1);

namespace Vrok\MonitoringBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand('monitor:send-alive-message')]
class SendAliveMessageCommand extends Command
{
    public function __construct(
        private string $monitorAddress,
        private string $appName,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('monitor:send-alive-message')
            ->setDescription('Sends an email to the configured monitor address with a notification that the app is alive and can send emails.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $subject = "Service $this->appName is alive!";
        $body = "Automatic message from $this->appName: "
            .'The service is alive and can send emails'
            ."\nat ".date(DATE_ATOM)
            // we need a comparable integer for the --capture-max option of the
            // nagios/icinga plugin "check_imap_receive"
            .' (timestamp '.time().')'
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
        $this->mailer->send($email);

        $success = "Sent alive-message for $this->monitorAddress to the mail transport or message bus";
        $this->logger->info($success);
        $output->writeln($success);

        return 0;
    }
}
