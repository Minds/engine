<?php

namespace Spec\Minds\Core\Supermind\Events;

use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Supermind\Events\Events;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EventsSpec extends ObjectBehavior
{
    /** @var EventsDispatcher */
    private $eventsDispatcher;

    public function let(EventsDispatcher $eventsDispatcher)
    {
        $this->beConstructedWith($eventsDispatcher);

        $this->eventsDispatcher = $eventsDispatcher;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Events::class);
    }

    public function it_should_register_events()
    {
        $this->eventsDispatcher->register('acl:read', 'supermind', Argument::type('callable'))
            ->shouldBeCalled();

        $this->eventsDispatcher->register('acl:write', 'supermind', Argument::type('callable'))
            ->shouldBeCalled();

        $this->eventsDispatcher->register('export:extender', 'activity', Argument::type('callable'))
            ->shouldBeCalled();

        $this->register();
    }
}
