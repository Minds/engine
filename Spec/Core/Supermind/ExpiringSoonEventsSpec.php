<!-- It should not get offers that have expired already (aka older than 7days)

It should not get offers that aren't yet eligible for a reminder (aka younger than 6 days)

It should not get offers that we've already triggered events for (aka create date is before the cache's last value)

It should get offers that are expiring soon, not expired and we haven't triggered yet  -->

<?php

namespace Spec\Minds\Core\Supermind;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Supermind\Delegates\EventsDelegate;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ExpiringSoonEventsSpec extends ObjectBehavior
{
    /** @var ActionEventsTopic */
    private $actionEventsTopic;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    public function let(ActionEventsTopic $actionEventsTopic, EntitiesBuilder $entitiesBuilder)
    {
        $this->beConstructedWith($actionEventsTopic, $entitiesBuilder);

        $this->actionEventsTopic = $actionEventsTopic;
        $this->entitiesBuilder = $entitiesBuilder;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(EventsDelegate::class);
    }

    public function it_should_trigger_a_supermind_request_create_event(ActionEventsTopic $actionEventsTopic, EntitiesBuilder $entitiesBuilder)
    {
        $this->beConstructedWith($actionEventsTopic, $entitiesBuilder);

        // Acting user is the sender
        $user = (new User())
            ->set('guid', '123');
        $entitiesBuilder->single('123')
            ->shouldBeCalled()
            ->willReturn($user);

        $supermindRequest = new SupermindRequest();
        $supermindRequest->setSenderGuid('123')
            ->setReceiverGuid('456')
            ->setPaymentAmount(42)
            ->setPaymentMethod(0)
            ->setStatus(0)
            ->setPaymentTxId('789')
            ->setCreatedAt(12345);

        $actionEventsTopic->send(Argument::that(function ($actionEvent) {
            return $actionEvent->getAction() === 'supermind_request_create';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->onCompleteSupermindRequestCreation($supermindRequest);
    }


    public function it_should_trigger_a_supermind_request_accept_event(ActionEventsTopic $actionEventsTopic, EntitiesBuilder $entitiesBuilder)
    {
        $this->beConstructedWith($actionEventsTopic, $entitiesBuilder);

        // Acting user is the receiver
        $user = (new User())
            ->set('guid', '456');
        $entitiesBuilder->single('456')
            ->shouldBeCalled()
            ->willReturn($user);

        $supermindRequest = new SupermindRequest();
        $supermindRequest->setSenderGuid('123')
            ->setReceiverGuid('456')
            ->setPaymentAmount(42)
            ->setPaymentMethod(0)
            ->setStatus(0)
            ->setPaymentTxId('789')
            ->setCreatedAt(12345);

        $actionEventsTopic->send(Argument::that(function ($actionEvent) {
            return $actionEvent->getAction() === 'supermind_request_accept';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->onAcceptSupermindRequest($supermindRequest);
    }

    public function it_should_trigger_a_supermind_request_reject_event(ActionEventsTopic $actionEventsTopic, EntitiesBuilder $entitiesBuilder)
    {
        $this->beConstructedWith($actionEventsTopic, $entitiesBuilder);

        // Acting user is the receiver
        $user = (new User())
            ->set('guid', '456');
        $entitiesBuilder->single('456')
            ->shouldBeCalled()
            ->willReturn($user);

        $supermindRequest = new SupermindRequest();
        $supermindRequest->setSenderGuid('123')
            ->setReceiverGuid('456')
            ->setPaymentAmount(42)
            ->setPaymentMethod(0)
            ->setStatus(0)
            ->setPaymentTxId('789')
            ->setCreatedAt(12345);

        $actionEventsTopic->send(Argument::that(function ($actionEvent) {
            return $actionEvent->getAction() === 'supermind_request_reject';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->onRejectSupermindRequest($supermindRequest);
    }


    public function it_should_trigger_a_supermind_request_expire_event(ActionEventsTopic $actionEventsTopic, EntitiesBuilder $entitiesBuilder)
    {
        $this->beConstructedWith($actionEventsTopic, $entitiesBuilder);

        // Acting user is the receiver
        $user = (new User())
            ->set('guid', '456');
        $entitiesBuilder->single('456')
            ->shouldBeCalled()
            ->willReturn($user);

        $supermindRequest = new SupermindRequest();
        $supermindRequest->setSenderGuid('123')
            ->setReceiverGuid('456')
            ->setPaymentAmount(42)
            ->setPaymentMethod(0)
            ->setStatus(0)
            ->setPaymentTxId('789')
            ->setCreatedAt(12345);

        $actionEventsTopic->send(Argument::that(function ($actionEvent) {
            return $actionEvent->getAction() === 'supermind_request_expire';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->onExpireSupermindRequest($supermindRequest);
    }
}
