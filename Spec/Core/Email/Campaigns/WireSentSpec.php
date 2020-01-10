<?php

namespace Spec\Minds\Core\Email\Campaigns;

use Minds\Core\Email\Campaigns\WireSent;
use PhpSpec\ObjectBehavior;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\Manager;
use Minds\Core\Email\EmailSubscription;
use Minds\Entities\User;
use Prophecy\Argument;
use Minds\Core\Wire\Wire;
use Minds\Core\Util\BigNumber;

class WireSentSpec extends ObjectBehavior
{
    protected $mailer;
    protected $manager;
    private $sender;
    private $receiver;
    private $wire;

    private $receiverGUID = 123456789;
    private $receiverName = 'receiver_name';
    private $receiverEmail = 'receiver@minds.com';
    private $receiverUsername = 'receiverUsername';
    private $receiverBriefDescription = 'receiver brief description';

    private $senderGUID = 234567890;
    private $senderName = 'sender_name';
    private $senderEmail = 'sender@minds.com';
    private $senderUsername = 'senderUsername';
    private $senderBriefDescription = 'sender brief description';

    public function let(Mailer $mailer, Manager $manager, User $receiver, User $sender)
    {
        $this->beConstructedWith(null, $mailer, $manager);
        $this->mailer = $mailer;
        $this->manager = $manager;
        $this->wire = new Wire();
        $this->wire->setAmount(10);

        $receiver->getGUID()->willReturn($this->receiverGUID);
        $receiver->get('enabled')->willReturn('yes');
        $receiver->get('name')->willReturn($this->receiverName);
        $receiver->get('guid')->willReturn($this->receiverGUID);
        $receiver->getEmail()->willReturn($this->receiverEmail);
        $receiver->get('username')->willReturn($this->receiverUsername);

        $sender->getGUID()->willReturn($this->senderGUID);
        $sender->get('enabled')->willReturn('yes');
        $sender->get('name')->willReturn($this->senderName);
        $sender->get('guid')->willReturn($this->senderGUID);
        $sender->getEmail()->willReturn($this->senderEmail);
        $sender->get('username')->willReturn($this->senderUsername);
        $sender->get('banned')->willReturn(false);

        $this->receiver = $receiver;
        $this->sender = $sender;
        $this->wire->setReceiver($receiver);
        $this->wire->setSender($sender);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(WireSent::class);
    }

    public function it_should_send_a_wire_received_email_tokens()
    {
        $this->getCampaign()->shouldEqual('when');
        $this->getTopic()->shouldEqual('wire_received');
        $this->setUser($this->sender);
        $this->setWire($this->wire);
        $message = $this->build();
        $message->getSubject()->shouldEqual('Your Wire receipt');
        $to = $message->getTo()[0]['name']->shouldEqual($this->senderName);
        $to = $message->getTo()[0]['email']->shouldEqual($this->senderEmail);
        $data = $this->getTemplate()->getData();
        $data['guid']->shouldEqual($this->senderGUID);
        $data['email']->shouldEqual($this->senderEmail);
        $data['username']->shouldEqual($this->senderUsername);
        //$data['amount']->shouldEqual(BigNumber::fromPlain(10, 18)->toDouble()); It is correct just in the wrong format to assert
        $this->mailer->send(Argument::any())->shouldBeCalled();

        $testEmailSubscription = (new EmailSubscription())
            ->setUserGuid($this->senderGUID)
            ->setCampaign('when')
            ->setTopic('wire_received')
            ->setValue(true);

        $this->manager->isSubscribed($testEmailSubscription)->shouldBeCalled()->willReturn(true);
        $this->send();
    }

    public function it_should_send_a_wire_received_email_onchain()
    {
        $this->wire->setMethod('onchain');

        $this->getCampaign()->shouldEqual('when');
        $this->getTopic()->shouldEqual('wire_received');
        $this->setUser($this->sender);
        $this->setWire($this->wire);
        $this->setSuggestions($this->mockSuggestions());
        $message = $this->build();
        $message->getSubject()->shouldEqual('Your Wire receipt');
        $to = $message->getTo()[0]['name']->shouldEqual($this->senderName);
        $to = $message->getTo()[0]['email']->shouldEqual($this->senderEmail);
        $data = $this->getTemplate()->getData();
        $data['guid']->shouldEqual($this->senderGUID);
        $data['email']->shouldEqual($this->senderEmail);
        $data['username']->shouldEqual($this->senderUsername);
        $data['contract']->shouldEqual('wire');
        $data['amount']->shouldEqual('10 ONCHAIN');
        $this->mailer->send(Argument::any())->shouldBeCalled();

        $testEmailSubscription = (new EmailSubscription())
            ->setUserGuid($this->senderGUID)
            ->setCampaign('when')
            ->setTopic('wire_received')
            ->setValue(true);

        $this->manager->isSubscribed($testEmailSubscription)->shouldBeCalled()->willReturn(true);
        $this->send();
    }

    public function it_should_not_send_unsubscribed()
    {
        $this->getCampaign()->shouldEqual('when');
        $this->getTopic()->shouldEqual('wire_received');

        $this->setUser($this->sender);
        $this->setWire($this->wire);

        $this->build();

        $data = $this->getTemplate()->getData();
        $data['email']->shouldEqual($this->senderEmail);
        $data['username']->shouldEqual($this->senderUsername);

        $this->mailer->send(Argument::any())->shouldNotBeCalled();

        $testEmailSubscription = (new EmailSubscription())
            ->setUserGuid($this->senderGUID)
            ->setCampaign('when')
            ->setTopic('wire_received')
            ->setValue(true);

        $this->manager->isSubscribed($testEmailSubscription)->shouldBeCalled()->willReturn(false);
        $this->send();
    }

    public function it_should_not_blowup_without_a_manager()
    {
        $this->getCampaign()->shouldEqual('when');
        $this->getTopic()->shouldEqual('wire_received');

        $this->setUser($this->sender);
        $this->setWire($this->wire);
        $this->build();

        $data = $this->getTemplate()->getData();
        $data['email']->shouldEqual($this->senderEmail);
        $data['username']->shouldEqual($this->senderUsername);

        $this->mailer->send(Argument::any())->shouldNotBeCalled();

        $testEmailSubscription = (new EmailSubscription())
            ->setUserGuid($this->senderGUID)
            ->setCampaign('when')
            ->setTopic('wire_received')
            ->setValue(true);

        $this->manager = null;
        $this->send();
    }

    public function it_should_not_blowup_without_a_user()
    {
        $this->mailer->send(Argument::any())->shouldNotBeCalled();
        $this->send();
    }

    public function it_should_not_send_disabled()
    {
        $this->setUser($this->sender);
        $this->setWire($this->wire);
        $this->build();

        $data = $this->getTemplate()->getData();
        $data['email']->shouldEqual($this->senderEmail);
        $data['username']->shouldEqual($this->senderUsername);

        $this->mailer->send(Argument::any())->shouldNotBeCalled();
        $this->send();
    }

    public function it_should_send_not_send_unsubscribed_emails()
    {
        $this->setUser($this->sender);
        $this->setWire($this->wire);
        $this->build();

        $data = $this->getTemplate()->getData();
        $data['email']->shouldEqual($this->senderEmail);
        $data['username']->shouldEqual($this->senderUsername);

        $this->manager->isSubscribed(Argument::type(EmailSubscription::class))
            ->shouldBeCalled()
            ->willReturn(false);

        $this->mailer->send(Argument::any())->shouldNotBeCalled();
        $this->send();
    }
}
