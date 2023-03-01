<?php

namespace Spec\Minds\Core\Boost\V3\Events;

use Minds\Core\Boost\V3\Events\Events;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Security\ACL;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EventsSpec extends ObjectBehavior
{
    /** @var EventsDispatcher */
    protected $eventsDispatcher;

    protected $acl;

    public function let(
        EventsDispatcher $eventsDispatcher,
        ACL $acl
    ) {
        $this->eventsDispatcher = $eventsDispatcher;
        $this->acl = $acl;

        $this->beConstructedWith($eventsDispatcher, $acl);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Events::class);
    }

    // try mock event and event->setResponse should be called
    public function it_should_register_events()
    {
        $this->eventsDispatcher->register('acl:read', 'boost', Argument::any())
            ->shouldBeCalled();

        $this->register();
    }
}
