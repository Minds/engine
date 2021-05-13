<?php

namespace Spec\Minds\Core\EventStreams\Topics;

use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ActionEventsTopicSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ActionEventsTopic::class);
    }

    public function it_should_send_an_event_to_stream()
    {
        $event = new ActionEvent();
        $event->setAction('vote');
        $event->setUser(new User());
        $event->setEntity(new Activity());

        $this->send($event)
            ->shouldReturn(true);
    }

    // public function it_should_consume_an_event()
    // {
    //     $this->consume('sub-id', function ($event) {
    //     });
    // }
}
