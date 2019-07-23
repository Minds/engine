<?php

namespace Spec\Minds\Core\Analytics;

use Minds\Core\Analytics\Events;
use Minds\Core\Di\Di;
use Minds\Core\Events\EventsDispatcher;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EventsSpec extends ObjectBehavior
{
    /** @var EventsDispatcher */
    protected $dispatcher;

    function let(EventsDispatcher $dispatcher)
    {
        Di::_()->bind('EventsDispatcher', function ($di) use ($dispatcher) {
            return $dispatcher->getWrappedObject();
        });
        $this->dispatcher = $dispatcher;
    }

    public function it_is_initialisable()
    {
        $this->shouldHaveType(Events::class);
    }

    public function is_should_register_event()
    {
        $this->dispatcher->register('user_state_change', 'all', Argument::any())
            ->shouldBeCalled();

        $this->register();
    }
}
