<?php

namespace Spec\Minds\Core\Supermind\Delegates;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Supermind\Delegates\EventsDelegate;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Supermind\Models\SupermindRequest;
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

    public function it_should_trigger_a_supermind_request_created_event()
    {
        $supermindRequest = new SupermindRequest();
        $supermindRequest->setSenderGuid('123')
            ->setReceiverGuid('456')
            ->setPaymentAmount(42)
            ->setPaymentMethod(0)
            ->setStatus(0)
            ->setPaymentTxId(789)
            ->setCreatedAt(12345);

        $this->buildUser('123')->shouldBeCalled();

        $this->actionEventsTopic->send(Argument::that(function ($actionEvent) {
            return $actionEvent->getAction() === 'supermind_request_create';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->onCompleteSupermindRequestCreation($supermindRequest);
    }
}
