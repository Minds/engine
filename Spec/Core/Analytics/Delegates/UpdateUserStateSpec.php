<?php

namespace Spec\Minds\Core\Analytics\Delegates;

use Minds\Core\Analytics\Delegates\UpdateUserState;
use Minds\Core\Analytics\UserStates\UserState;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class UpdateUserStateSpec extends ObjectBehavior
{
    public function let(UserState $userState, User $user)
    {
        $this->beConstructedWith($userState, $user);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UpdateUserState::class);
    }

    public function it_should_update_user_if_activity_state_change(UserState $userState, User $user)
    {
        $user->getUserState()->shouldBeCalled()->willReturn(UserState::STATE_UNKNOWN);
        $userState->getState()->shouldBeCalled()->willReturn(UserState::STATE_NEW);
        $userState->getReferenceDateMs()->shouldBeCalled()->willReturn(1563753600000);
        $userState->getUserGuid()->shouldBeCalled()->willReturn(1001);
        $userState->export()->shouldBeCalled()->willReturn([]);
        $user->setUserState(UserState::STATE_NEW)->shouldBeCalled()->willReturn($user);
        $user->setUserStateUpdatedMs(1563753600000)->shouldBeCalled()->willReturn($user);
        $user->save()->shouldBeCalled();

        $this->update();
    }

    public function it_should_not_update_user_if_no_activity_state_change(UserState $userState, User $user)
    {
        $user->getUserState()->shouldBeCalled()->willReturn(UserState::STATE_NEW);
        $userState->getState()->shouldBeCalled()->willReturn(UserState::STATE_NEW);
        $user->save()->shouldNotBeCalled();

        $this->update();
    }
}
