<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Config\Events;

use Minds\Core\Config\Events\Events;
use Minds\Core\Events\EventsDispatcher;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class EventsSpec extends ObjectBehavior
{
    private Collaborator $eventsDispatcherMock;

    public function let(EventsDispatcher $eventsDispatcherMock)
    {
        $this->eventsDispatcherMock = $eventsDispatcherMock;
        $this->beConstructedWith($eventsDispatcherMock);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(Events::class);
    }

    public function it_should_register(): void
    {
        $this->eventsDispatcherMock->register(
            'config:extender',
            'config',
            Argument::cetera()
        )->shouldBeCalled();

        $this->register();
    }
}
