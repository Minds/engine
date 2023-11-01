<?php

namespace Spec\Minds\Core\Channels\Delegates;

use Minds\Core\Channels\Delegates\Ban;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Events\EventsDispatcher;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class BanSpec extends ObjectBehavior
{
    /** @var EventsDispatcher */
    protected $eventsDispatcher;

    protected Collaborator $saveMock;

    public function let(EventsDispatcher $eventsDispatcher, Save $saveMock)
    {
        $this->beConstructedWith($eventsDispatcher, $saveMock);
        $this->eventsDispatcher = $eventsDispatcher;
        $this->saveMock = $saveMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Ban::class);
    }

    public function it_should_ban(User $user)
    {
        $user->set('ban_reason', 'phpspec')
            ->shouldBeCalled();

        $user->set('banned', 'yes')
            ->shouldBeCalled();

        $user->set('code', '')
            ->shouldBeCalled();

        $this->saveMock->setEntity($user)->willReturn($this->saveMock);
        $this->saveMock->withMutatedAttributes(['ban_reason', 'banned'])->willReturn($this->saveMock);
        $this->saveMock->save()->shouldBeCalled()->willReturn(true);

        $this->eventsDispatcher->trigger('ban', 'user', $user)
            ->shouldBeCalled();

        $this
            ->ban($user, 'phpspec', false)
            ->shouldReturn(true);
    }
}
