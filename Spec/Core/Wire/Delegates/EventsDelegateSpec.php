<?php

namespace Spec\Minds\Core\Wire\Delegates;

use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Wire\Delegates\EventsDelegate;
use Minds\Core\Wire\Wire;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EventsDelegateSpec extends ObjectBehavior
{
    /** @var ActionEventsTopic */
    protected $actionEventsTopic;

    public function let(ActionEventsTopic $actionEventsTopic)
    {
        $this->beConstructedWith($actionEventsTopic);
        $this->actionEventsTopic = $actionEventsTopic;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(EventsDelegate::class);
    }

    public function it_should_submit_action_event(Wire $wire, User $sender)
    {
        $wire->getGuid()
            ->willReturn('123');
    
        $wire->getSender()
            ->willReturn($sender);

        $sender->getGuid()
            ->willReturn('456');

        $this->actionEventsTopic->send(Argument::that(function ($actionEvent) {
            return $actionEvent->getEntity()->getGuid() === '123'
                && $actionEvent->getUser()->getGuid() === '456';
        }))
            ->willReturn(true);

        $this->onAdd($wire);
    }
}
