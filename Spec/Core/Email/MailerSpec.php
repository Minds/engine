<?php

namespace Spec\Minds\Core\Email;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use PHPMailer\PHPMailer\PHPMailer;

use Minds\Core\Queue\Client as Queue;
use Minds\Core\Email\SpamFilter;
use Minds\Core\Email\Message;
use Minds\Core\Log\Logger;

class MailerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Email\Mailer');
    }

    public function it_should_not_send_a_blacklist_domain(PHPMailer $mailer, Queue $queue, SpamFilter $filter, Message $message)
    {
        $this->beConstructedWith($mailer, $queue, $filter);

        $message->to = [[ 'email' => 'you@yomail.com', 'name' => 'Spam' ]];
        $message->from = [ 'email' => 'me@minds.com', 'name' => 'Sender' ];
        $this->send($message);

        $this->getStats()->shouldHaveKeyWithValue('failed', 1);
    }

    public function it_should_not_send_to_an_imported_activitypub_actor(PHPMailer $mailer, Queue $queue, SpamFilter $filter, Message $message, Logger $logger)
    {
        $this->beConstructedWith($mailer, $queue, $filter, $logger);

        $filter->isSpam('activitypub-imported@minds.com')->willReturn(false);
        $message->to = [[ 'email' => 'activitypub-imported@minds.com', 'name' => 'Imported actor' ]];
        $message->from = [ 'email' => 'no-reply@minds.com', 'name' => 'Minds' ];

        $mailer->AddAddress(Argument::cetera())->shouldNotBeCalled();
        $mailer->Send()->shouldNotBeCalled();

        $this->send($message);

        $this->getStats()->shouldHaveKeyWithValue('sent', 0);
        $this->getStats()->shouldHaveKeyWithValue('failed', 0);
    }
}
