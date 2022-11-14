<?php

namespace Spec\Minds\Core\Email\V2\Campaigns\Recurring\Supermind;

use Minds\Core\Config\Config;
use Minds\Core\Email\Manager;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Campaigns\Recurring\Supermind\Supermind;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Supermind\SupermindRequestPaymentMethod;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SupermindSpec extends ObjectBehavior
{
    /** @var Template */
    private $template;

    /** @var Mailer */
    private $mailer;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var Config */
    private $config;

    /** @var Manager */
    private $emailManager;

    public function let(
        Template $template,
        Mailer $mailer,
        EntitiesBuilder $entitiesBuilder,
        Config $config,
        Manager $emailManager
    ) {
        $this->beConstructedWith(
            $template,
            $mailer,
            $entitiesBuilder,
            $config,
            $emailManager
        );
        $this->template = $template;
        $this->mailer = $mailer;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->config = $config;
        $this->emailManager = $emailManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Supermind::class);
    }

    public function it_should_build_a_supermind_request_sent_email(
        SupermindRequest $supermindRequest,
        User $requester,
        User $receiver
    ) {
        $topic = 'supermind_request_sent';
        $requesterGuid = '123';
        $receiverGuid = '234';
        $receiverUsername = 'minds_receiver';
        $requesterUsername = 'minds_requester';
        $supermindRequestGuid = '345';
        $requesterEmail = 'no-reply@minds.com';
        $paymentMethod = SupermindRequestPaymentMethod::CASH;
        $paymentAmount = '100';
        $campaign = 'when';
        $tracking = '__e_ct_guid=minds_requester&campaign=when&topic=supermind_request_sent&state=new';
        $bodyText = 'They have 7 days to reply and accept this offer.';
        $headerText = 'You sent a $100 Supermind offer to @minds_receiver';
        $bodySubjectText = null;

        $supermindRequest->getSenderGuid()
            ->shouldBeCalled()
            ->willReturn($requesterGuid);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->getGuid()
            ->shouldBeCalled()
            ->willReturn($supermindRequestGuid);

        $supermindRequest->getGuid()
            ->shouldBeCalled()
            ->willReturn($supermindRequestGuid);

        $receiver->getUsername()
            ->shouldBeCalled()
            ->willReturn($receiverUsername);

        $requester->getEmail()
            ->shouldBeCalled()
            ->willReturn($requesterEmail);

        $requester->getGuid()
            ->shouldBeCalled()
            ->willReturn($requesterGuid);

        $requester->get('username')
            ->shouldBeCalled()
            ->willReturn($requesterUsername);

        $requester->get('guid')
            ->shouldBeCalled()
            ->willReturn($requesterGuid);

        $requester->get('name')
            ->shouldBeCalled()
            ->willReturn($requesterUsername);

        $requester->getGuid()
            ->shouldBeCalled()
            ->willReturn($requesterUsername);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $supermindRequest->getPaymentAmount()
            ->shouldBeCalled()
            ->willReturn($paymentAmount);

        $this->setTopic($topic);
        $this->setSupermindRequest($supermindRequest);

        $this->template->setTemplate('default.v2.tpl')
            ->shouldBeCalled();

        $this->template->setBody('./template.tpl')
            ->shouldBeCalled();

        $this->entitiesBuilder->single($requesterGuid)
            ->shouldBeCalled()
            ->willReturn($requester);

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);

        $this->template->set('user', $requester)
            ->shouldBeCalled();

        $this->template->set('username', $requesterUsername)
            ->shouldBeCalled();

        $this->template->set('email', $requesterEmail)
            ->shouldBeCalled();

        $this->template->set('guid', $requesterGuid)
            ->shouldBeCalled();

        $this->template->set('campaign', $campaign)
            ->shouldBeCalled();

        $this->template->set('topic', $topic)
            ->shouldBeCalled();

        $this->template->set('tracking', $tracking)
            ->shouldBeCalled();

        $this->template->set('title', '')
            ->shouldBeCalled();

        $this->template->set('state', '')
            ->shouldBeCalled();

        $this->template->set('preheader', $bodyText)
            ->shouldBeCalled();

        $this->template->set('bodyText', $bodyText)
            ->shouldBeCalled();

        $this->template->set('headerText', $headerText)
            ->shouldBeCalled();

        $this->template->set('bodySubjectText', $bodySubjectText)
            ->shouldBeCalled();

        $this->template->set('actionButton', Argument::any())
            ->shouldBeCalled();

        $this->build();
    }

    public function it_should_build_a_supermind_request_received_email(
        SupermindRequest $supermindRequest,
        User $requester,
        User $receiver
    ) {
        $topic = 'supermind_request_received';
        $requesterGuid = '123';
        $receiverGuid = '234';
        $receiverUsername = 'minds_receiver';
        $requesterUsername = 'minds_requester';
        $supermindRequestGuid = '345';
        $receiverEmail = 'no-reply@minds.com';
        $paymentMethod = SupermindRequestPaymentMethod::CASH;
        $paymentAmount = '100';
        $campaign = 'when';
        $tracking = '__e_ct_guid=234&campaign=when&topic=supermind_request_received&state=new';
        $bodyText = 'You have 7 days to reply and accept this offer.';
        $headerText = '@minds_requester sent you a $100 Supermind offer';
        $bodySubjectText = '@minds_receiver,';

        $supermindRequest->getSenderGuid()
            ->shouldBeCalled()
            ->willReturn($requesterGuid);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->getGuid()
            ->shouldBeCalled()
            ->willReturn($supermindRequestGuid);

        $supermindRequest->getGuid()
            ->shouldBeCalled()
            ->willReturn($supermindRequestGuid);
        
        $receiver->get('username')
            ->shouldBeCalled()
            ->willReturn($receiverUsername);

        $receiver->getUsername()
            ->shouldBeCalled()
            ->willReturn($receiverUsername);

        $receiver->getGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $receiver->get('guid')
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $receiver->getEmail()
            ->shouldBeCalled()
            ->willReturn($receiverEmail);

        $receiver->get('name')
            ->shouldBeCalled()
            ->willReturn($receiverUsername);
        
        $receiver->get('username')
            ->shouldBeCalled()
            ->willReturn($receiverUsername);

        $requester->getUsername()
            ->shouldBeCalled()
            ->willReturn($requesterUsername);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $supermindRequest->getPaymentAmount()
            ->shouldBeCalled()
            ->willReturn($paymentAmount);

        $this->setTopic($topic);
        $this->setSupermindRequest($supermindRequest);

        $this->template->setTemplate('default.v2.tpl')
            ->shouldBeCalled();

        $this->template->setBody('./template.tpl')
            ->shouldBeCalled();

        $this->entitiesBuilder->single($requesterGuid)
            ->shouldBeCalled()
            ->willReturn($requester);

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);

        $this->template->set('user', $receiver)
            ->shouldBeCalled();

        $this->template->set('username', $receiverUsername)
            ->shouldBeCalled();

        $this->template->set('email', $receiverEmail)
            ->shouldBeCalled();

        $this->template->set('guid', $receiverGuid)
            ->shouldBeCalled();

        $this->template->set('campaign', $campaign)
            ->shouldBeCalled();

        $this->template->set('topic', $topic)
            ->shouldBeCalled();

        $this->template->set('tracking', $tracking)
            ->shouldBeCalled();

        $this->template->set('title', '')
            ->shouldBeCalled();

        $this->template->set('state', '')
            ->shouldBeCalled();

        $this->template->set('preheader', $bodyText)
            ->shouldBeCalled();

        $this->template->set('bodyText', $bodyText)
            ->shouldBeCalled();

        $this->template->set('headerText', $headerText)
            ->shouldBeCalled();

        $this->template->set('bodySubjectText', $bodySubjectText)
            ->shouldBeCalled();

        $this->template->set('actionButton', Argument::any())
            ->shouldBeCalled();

        $this->build();
    }

    public function it_should_build_a_wire_received_email(
        SupermindRequest $supermindRequest,
        User $requester,
        User $receiver
    ) {
        $topic = 'wire_received';
        $requesterGuid = '123';
        $receiverGuid = '234';
        $receiverUsername = 'minds_receiver';
        $requesterUsername = 'minds_requester';
        $supermindRequestGuid = '345';
        $receiverEmail = 'no-reply@minds.com';
        $paymentMethod = SupermindRequestPaymentMethod::CASH;
        $paymentAmount = '100';
        $campaign = 'when';
        $tracking = '__e_ct_guid=234&campaign=when&topic=wire_received&state=new';
        $bodyText = 'You have 7 days to reply and accept this offer.';
        $headerText = '@minds_requester sent you a $100 Supermind offer';
        $bodySubjectText = '@minds_receiver,';

        $supermindRequest->getSenderGuid()
            ->shouldBeCalled()
            ->willReturn($requesterGuid);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->getGuid()
            ->shouldBeCalled()
            ->willReturn($supermindRequestGuid);

        $supermindRequest->getGuid()
            ->shouldBeCalled()
            ->willReturn($supermindRequestGuid);
        
        $receiver->get('username')
            ->shouldBeCalled()
            ->willReturn($receiverUsername);

        $receiver->getUsername()
            ->shouldBeCalled()
            ->willReturn($receiverUsername);

        $receiver->getGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $receiver->get('guid')
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $receiver->getEmail()
            ->shouldBeCalled()
            ->willReturn($receiverEmail);

        $receiver->get('name')
            ->shouldBeCalled()
            ->willReturn($receiverUsername);
        
        $receiver->get('username')
            ->shouldBeCalled()
            ->willReturn($receiverUsername);

        $requester->getUsername()
            ->shouldBeCalled()
            ->willReturn($requesterUsername);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $supermindRequest->getPaymentAmount()
            ->shouldBeCalled()
            ->willReturn($paymentAmount);

        $this->setTopic($topic);
        $this->setSupermindRequest($supermindRequest);

        $this->template->setTemplate('default.v2.tpl')
            ->shouldBeCalled();

        $this->template->setBody('./template.tpl')
            ->shouldBeCalled();

        $this->entitiesBuilder->single($requesterGuid)
            ->shouldBeCalled()
            ->willReturn($requester);

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);

        $this->template->set('user', $receiver)
            ->shouldBeCalled();

        $this->template->set('username', $receiverUsername)
            ->shouldBeCalled();

        $this->template->set('email', $receiverEmail)
            ->shouldBeCalled();

        $this->template->set('guid', $receiverGuid)
            ->shouldBeCalled();

        $this->template->set('campaign', $campaign)
            ->shouldBeCalled();

        $this->template->set('topic', $topic)
            ->shouldBeCalled();

        $this->template->set('tracking', $tracking)
            ->shouldBeCalled();

        $this->template->set('title', '')
            ->shouldBeCalled();

        $this->template->set('state', '')
            ->shouldBeCalled();

        $this->template->set('preheader', $bodyText)
            ->shouldBeCalled();

        $this->template->set('bodyText', $bodyText)
            ->shouldBeCalled();

        $this->template->set('headerText', $headerText)
            ->shouldBeCalled();

        $this->template->set('bodySubjectText', $bodySubjectText)
            ->shouldBeCalled();

        $this->template->set('actionButton', Argument::any())
            ->shouldBeCalled();

        $this->build();
    }

    public function it_should_build_a_supermind_request_accepted_email(
        SupermindRequest $supermindRequest,
        User $requester,
        User $receiver
    ) {
        $topic = 'supermind_request_accepted';
        $requesterGuid = '123';
        $receiverGuid = '234';
        $replyActivityGuid = '456';
        $receiverUsername = 'minds_receiver';
        $requesterUsername = 'minds_requester';
        $supermindRequestGuid = '345';
        $requesterEmail = 'no-reply@minds.com';
        $paymentMethod = SupermindRequestPaymentMethod::CASH;
        $paymentAmount = '100';
        $campaign = 'when';
        $tracking = '__e_ct_guid=minds_requester&campaign=when&topic=supermind_request_accepted&state=new';
        $bodyText = '$100 was sent to @minds_receiver for their reply.';
        $headerText = 'Congrats! @minds_receiver replied to your Supermind offer';
        $bodySubjectText = null;

        $supermindRequest->getSenderGuid()
            ->shouldBeCalled()
            ->willReturn($requesterGuid);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->getGuid()
            ->shouldBeCalled()
            ->willReturn($supermindRequestGuid);

        $supermindRequest->getGuid()
            ->shouldBeCalled()
            ->willReturn($supermindRequestGuid);

        $supermindRequest->getReplyActivityGuid()
            ->shouldBeCalled()
            ->willReturn($replyActivityGuid);

        $receiver->getUsername()
            ->shouldBeCalled()
            ->willReturn($receiverUsername);

        $requester->getEmail()
            ->shouldBeCalled()
            ->willReturn($requesterEmail);

        $requester->getGuid()
            ->shouldBeCalled()
            ->willReturn($requesterGuid);

        $requester->get('username')
            ->shouldBeCalled()
            ->willReturn($requesterUsername);

        $requester->get('guid')
            ->shouldBeCalled()
            ->willReturn($requesterGuid);

        $requester->get('name')
            ->shouldBeCalled()
            ->willReturn($requesterUsername);

        $requester->getGuid()
            ->shouldBeCalled()
            ->willReturn($requesterUsername);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $supermindRequest->getPaymentAmount()
            ->shouldBeCalled()
            ->willReturn($paymentAmount);

        $this->setTopic($topic);
        $this->setSupermindRequest($supermindRequest);

        $this->template->setTemplate('default.v2.tpl')
            ->shouldBeCalled();

        $this->template->setBody('./template.tpl')
            ->shouldBeCalled();

        $this->entitiesBuilder->single($requesterGuid)
            ->shouldBeCalled()
            ->willReturn($requester);

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);

        $this->template->set('additionalCtaPath', 'https://www.minds.com/wallet/cash/transactions')
            ->shouldBeCalled();

        $this->template->set('additionalCtaText', 'View Billing')
            ->shouldBeCalled();

        $this->template->set('user', $requester)
            ->shouldBeCalled();

        $this->template->set('username', $requesterUsername)
            ->shouldBeCalled();

        $this->template->set('email', $requesterEmail)
            ->shouldBeCalled();

        $this->template->set('guid', $requesterGuid)
            ->shouldBeCalled();

        $this->template->set('campaign', $campaign)
            ->shouldBeCalled();

        $this->template->set('topic', $topic)
            ->shouldBeCalled();

        $this->template->set('tracking', $tracking)
            ->shouldBeCalled();

        $this->template->set('title', '')
            ->shouldBeCalled();

        $this->template->set('state', '')
            ->shouldBeCalled();

        $this->template->set('preheader', $bodyText)
            ->shouldBeCalled();

        $this->template->set('bodyText', $bodyText)
            ->shouldBeCalled();

        $this->template->set('headerText', $headerText)
            ->shouldBeCalled();

        $this->template->set('bodySubjectText', $bodySubjectText)
            ->shouldBeCalled();

        $this->template->set('actionButton', Argument::any())
            ->shouldBeCalled();

        $this->build();
    }

    public function it_should_build_a_supermind_request_rejected_email(
        SupermindRequest $supermindRequest,
        User $requester,
        User $receiver
    ) {
        $topic = 'supermind_request_rejected';
        $requesterGuid = '123';
        $receiverGuid = '234';
        $receiverUsername = 'minds_receiver';
        $requesterUsername = 'minds_requester';
        $supermindRequestGuid = '345';
        $requesterEmail = 'no-reply@minds.com';
        $paymentMethod = SupermindRequestPaymentMethod::CASH;
        $paymentAmount = '100';
        $campaign = 'when';
        $tracking = '__e_ct_guid=minds_requester&campaign=when&topic=supermind_request_rejected&state=new';
        $bodyText = 'Don\'t worry, you have not been charged. You can try increasing your offer to improve your chance of reply';
        $headerText = '@minds_receiver declined your Supermind offer';
        $bodySubjectText = null;

        $supermindRequest->getSenderGuid()
            ->shouldBeCalled()
            ->willReturn($requesterGuid);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->getGuid()
            ->shouldBeCalled()
            ->willReturn($supermindRequestGuid);

        $supermindRequest->getGuid()
            ->shouldBeCalled()
            ->willReturn($supermindRequestGuid);

        $receiver->getUsername()
            ->shouldBeCalled()
            ->willReturn($receiverUsername);

        $requester->getEmail()
            ->shouldBeCalled()
            ->willReturn($requesterEmail);

        $requester->getGuid()
            ->shouldBeCalled()
            ->willReturn($requesterGuid);

        $requester->get('username')
            ->shouldBeCalled()
            ->willReturn($requesterUsername);

        $requester->get('guid')
            ->shouldBeCalled()
            ->willReturn($requesterGuid);

        $requester->get('name')
            ->shouldBeCalled()
            ->willReturn($requesterUsername);

        $requester->getGuid()
            ->shouldBeCalled()
            ->willReturn($requesterUsername);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $supermindRequest->getPaymentAmount()
            ->shouldBeCalled()
            ->willReturn($paymentAmount);

        $this->setTopic($topic);
        $this->setSupermindRequest($supermindRequest);

        $this->template->setTemplate('default.v2.tpl')
            ->shouldBeCalled();

        $this->template->setBody('./template.tpl')
            ->shouldBeCalled();

        $this->entitiesBuilder->single($requesterGuid)
            ->shouldBeCalled()
            ->willReturn($requester);

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);

        $this->template->set('user', $requester)
            ->shouldBeCalled();

        $this->template->set('username', $requesterUsername)
            ->shouldBeCalled();

        $this->template->set('email', $requesterEmail)
            ->shouldBeCalled();

        $this->template->set('guid', $requesterGuid)
            ->shouldBeCalled();

        $this->template->set('campaign', $campaign)
            ->shouldBeCalled();

        $this->template->set('topic', $topic)
            ->shouldBeCalled();

        $this->template->set('tracking', $tracking)
            ->shouldBeCalled();

        $this->template->set('title', '')
            ->shouldBeCalled();

        $this->template->set('state', '')
            ->shouldBeCalled();

        $this->template->set('preheader', $bodyText)
            ->shouldBeCalled();

        $this->template->set('bodyText', $bodyText)
            ->shouldBeCalled();

        $this->template->set('headerText', $headerText)
            ->shouldBeCalled();

        $this->template->set('bodySubjectText', $bodySubjectText)
            ->shouldBeCalled();

        $this->template->set('actionButton', Argument::any())
            ->shouldBeCalled();

        $this->build();
    }

    public function it_should_build_a_supermind_request_expiring_soon_email(
        SupermindRequest $supermindRequest,
        User $requester,
        User $receiver
    ) {
        $topic = 'supermind_request_expiring_soon';
        $requesterGuid = '123';
        $receiverGuid = '234';
        $receiverUsername = 'minds_receiver';
        $requesterUsername = 'minds_requester';
        $supermindRequestGuid = '345';
        $receiverEmail = 'no-reply@minds.com';
        $paymentMethod = SupermindRequestPaymentMethod::CASH;
        $paymentAmount = '100';
        $campaign = 'when';
        $tracking = '__e_ct_guid=234&campaign=when&topic=supermind_request_expiring_soon&state=new';
        $bodyText = 'You have 24 hours remaining to review @minds_requester\'s $100 offer';
        $headerText = 'Your $100 Supermind offer expires tomorrow';
        $bodySubjectText = '@minds_receiver,';

        $supermindRequest->getSenderGuid()
            ->shouldBeCalled()
            ->willReturn($requesterGuid);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->getGuid()
            ->shouldBeCalled()
            ->willReturn($supermindRequestGuid);

        $supermindRequest->getGuid()
            ->shouldBeCalled()
            ->willReturn($supermindRequestGuid);
        
        $receiver->get('username')
            ->shouldBeCalled()
            ->willReturn($receiverUsername);

        $receiver->getUsername()
            ->shouldBeCalled()
            ->willReturn($receiverUsername);

        $receiver->getGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $receiver->get('guid')
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $receiver->getEmail()
            ->shouldBeCalled()
            ->willReturn($receiverEmail);

        $receiver->get('name')
            ->shouldBeCalled()
            ->willReturn($receiverUsername);
        
        $receiver->get('username')
            ->shouldBeCalled()
            ->willReturn($receiverUsername);

        $requester->getUsername()
            ->shouldBeCalled()
            ->willReturn($requesterUsername);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $supermindRequest->getPaymentAmount()
            ->shouldBeCalled()
            ->willReturn($paymentAmount);

        $this->setTopic($topic);
        $this->setSupermindRequest($supermindRequest);

        $this->template->setTemplate('default.v2.tpl')
            ->shouldBeCalled();

        $this->template->setBody('./template.tpl')
            ->shouldBeCalled();

        $this->entitiesBuilder->single($requesterGuid)
            ->shouldBeCalled()
            ->willReturn($requester);

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);

        $this->template->set('user', $receiver)
            ->shouldBeCalled();

        $this->template->set('username', $receiverUsername)
            ->shouldBeCalled();

        $this->template->set('email', $receiverEmail)
            ->shouldBeCalled();

        $this->template->set('guid', $receiverGuid)
            ->shouldBeCalled();

        $this->template->set('campaign', $campaign)
            ->shouldBeCalled();

        $this->template->set('topic', $topic)
            ->shouldBeCalled();

        $this->template->set('tracking', $tracking)
            ->shouldBeCalled();

        $this->template->set('title', '')
            ->shouldBeCalled();

        $this->template->set('state', '')
            ->shouldBeCalled();

        $this->template->set('preheader', $bodyText)
            ->shouldBeCalled();

        $this->template->set('bodyText', $bodyText)
            ->shouldBeCalled();

        $this->template->set('headerText', $headerText)
            ->shouldBeCalled();

        $this->template->set('bodySubjectText', $bodySubjectText)
            ->shouldBeCalled();

        $this->template->set('actionButton', Argument::any())
            ->shouldBeCalled();

        $this->build();
    }

    public function it_should_build_a_supermind_request_expired_email(
        SupermindRequest $supermindRequest,
        User $requester,
        User $receiver
    ) {
        $topic = 'supermind_request_expired';
        $requesterGuid = '123';
        $receiverGuid = '234';
        $receiverUsername = 'minds_receiver';
        $requesterUsername = 'minds_requester';
        $supermindRequestGuid = '345';
        $requesterEmail = 'no-reply@minds.com';
        $paymentMethod = SupermindRequestPaymentMethod::CASH;
        $paymentAmount = '100';
        $campaign = 'when';
        $tracking = '__e_ct_guid=minds_requester&campaign=when&topic=supermind_request_expired&state=new';
        $bodyText = 'Don\'t worry, you have not been charged. You can try increasing your offer to improve your chance of reply';
        $headerText = 'Your Supermind offer to @minds_receiver expired';
        $bodySubjectText = null;

        $supermindRequest->getSenderGuid()
            ->shouldBeCalled()
            ->willReturn($requesterGuid);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->getGuid()
            ->shouldBeCalled()
            ->willReturn($supermindRequestGuid);

        $supermindRequest->getGuid()
            ->shouldBeCalled()
            ->willReturn($supermindRequestGuid);

        $receiver->getUsername()
            ->shouldBeCalled()
            ->willReturn($receiverUsername);

        $requester->getEmail()
            ->shouldBeCalled()
            ->willReturn($requesterEmail);

        $requester->getGuid()
            ->shouldBeCalled()
            ->willReturn($requesterGuid);

        $requester->get('username')
            ->shouldBeCalled()
            ->willReturn($requesterUsername);

        $requester->get('guid')
            ->shouldBeCalled()
            ->willReturn($requesterGuid);

        $requester->get('name')
            ->shouldBeCalled()
            ->willReturn($requesterUsername);

        $requester->getGuid()
            ->shouldBeCalled()
            ->willReturn($requesterUsername);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $supermindRequest->getPaymentAmount()
            ->shouldBeCalled()
            ->willReturn($paymentAmount);

        $this->setTopic($topic);
        $this->setSupermindRequest($supermindRequest);

        $this->template->setTemplate('default.v2.tpl')
            ->shouldBeCalled();

        $this->template->setBody('./template.tpl')
            ->shouldBeCalled();

        $this->entitiesBuilder->single($requesterGuid)
            ->shouldBeCalled()
            ->willReturn($requester);

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);

        $this->template->set('user', $requester)
            ->shouldBeCalled();

        $this->template->set('username', $requesterUsername)
            ->shouldBeCalled();

        $this->template->set('email', $requesterEmail)
            ->shouldBeCalled();

        $this->template->set('guid', $requesterGuid)
            ->shouldBeCalled();

        $this->template->set('campaign', $campaign)
            ->shouldBeCalled();

        $this->template->set('topic', $topic)
            ->shouldBeCalled();

        $this->template->set('tracking', $tracking)
            ->shouldBeCalled();

        $this->template->set('title', '')
            ->shouldBeCalled();

        $this->template->set('state', '')
            ->shouldBeCalled();

        $this->template->set('preheader', $bodyText)
            ->shouldBeCalled();

        $this->template->set('bodyText', $bodyText)
            ->shouldBeCalled();

        $this->template->set('headerText', $headerText)
            ->shouldBeCalled();

        $this->template->set('bodySubjectText', $bodySubjectText)
            ->shouldBeCalled();

        $this->template->set('actionButton', Argument::any())
            ->shouldBeCalled();

        $this->build();
    }
}
