<?php

namespace Spec\Minds\Core\Reports;

use Minds\Core\Di\Di;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Reports\Events;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\Config;

class EventsSpec extends ObjectBehavior
{
    /** @var EventsDispatcher */
    protected $dispatcher;

    public function let(EventsDispatcher $dispatcher)
    {
        Di::_()->bind('EventsDispatcher', function ($di) use ($dispatcher) {
            return $dispatcher->getWrappedObject();
        });
        $this->dispatcher = $dispatcher;
    }


    public function it_is_initializable()
    {
        $this->shouldHaveType(Events::class);
    }

    public function it_should_register_the_user_ban_event()
    {
        $this->dispatcher->register('ban', 'user', Argument::any())
      ->shouldBeCalled();

        $this->register();
    }
}
