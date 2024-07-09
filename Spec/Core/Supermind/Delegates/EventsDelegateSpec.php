<?php

namespace Spec\Minds\Core\Supermind\Delegates;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Supermind\Delegates\EventsDelegate;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EventsDelegateSpec extends ObjectBehavior
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
            ->setPaymentTxID('789')
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
            ->setPaymentTxID('789')
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
            ->setPaymentTxID('789')
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

        $supermindRequest = new SupermindRequest();
        $supermindRequest->setSenderGuid('123')
            ->setReceiverGuid('456')
            ->setPaymentAmount(42)
            ->setPaymentMethod(0)
            ->setStatus(0)
            ->setPaymentTxID('789')
            ->setCreatedAt(12345);

        $actionEventsTopic->send(Argument::that(function ($actionEvent) {
            return $actionEvent->getAction() === 'supermind_request_expire';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->onExpireSupermindRequest($supermindRequest);
    }

    public function it_should_trigger_a_supermind_request_expire_soon_event(ActionEventsTopic $actionEventsTopic, EntitiesBuilder $entitiesBuilder)
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
            ->setPaymentTxID('789')
            ->setCreatedAt(12345);

        $actionEventsTopic->send(Argument::that(function ($actionEvent) {
            return $actionEvent->getAction() === 'supermind_request_expiring_soon';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->onSupermindRequestExpiringSoon($supermindRequest);
    }

    public function it_should_build_a_user(ActionEventsTopic $actionEventsTopic, EntitiesBuilder $entitiesBuilder)
    {
        $user = new User();
        $entitiesBuilder->single('123')
            ->shouldBeCalled()
            ->willReturn($user);

        $this->beConstructedWith($actionEventsTopic, $entitiesBuilder);

        $this->buildUser('123')
            ->shouldReturn($user);
    }

    public function it_should_return_null_if_entity_is_not_a_user(ActionEventsTopic $actionEventsTopic, EntitiesBuilder $entitiesBuilder)
    {
        $activity = new Activity();

        $entitiesBuilder->single('123')
            ->shouldBeCalled()
            ->willReturn($activity);

        $this->beConstructedWith($actionEventsTopic, $entitiesBuilder);

        $this->buildUser('123')
            ->shouldReturn(null);
    }
}
