<?php

namespace Spec\Minds\Core\Comments\Delegates;

use Minds\Core\Comments\Comment;
use Minds\Core\Events\Dispatcher;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CreateEventDispatcherSpec extends ObjectBehavior
{
    protected $eventsDispatcher;

    public function let(
        Dispatcher $eventsDispatcher
    ) {
        $this->beConstructedWith($eventsDispatcher);

        $this->eventsDispatcher = $eventsDispatcher;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Comments\Delegates\CreateEventDispatcher');
    }

    // EventsDispatcher cannot be tested yet
}
