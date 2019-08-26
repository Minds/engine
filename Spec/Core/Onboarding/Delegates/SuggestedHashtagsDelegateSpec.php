<?php

namespace Spec\Minds\Core\Onboarding\Delegates;

use Minds\Core\Hashtags\User\Manager;
use Minds\Core\Onboarding\Delegates\SuggestedHashtagsDelegate;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SuggestedHashtagsDelegateSpec extends ObjectBehavior
{
    protected $userHashtagsManager;

    public function let(Manager $userHashtagsManager)
    {
        $this->beConstructedWith($userHashtagsManager);

        $this->userHashtagsManager = $userHashtagsManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SuggestedHashtagsDelegate::class);
    }

    public function it_should_check_if_completed(User $user)
    {
        $this->userHashtagsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->userHashtagsManager);

        $this->userHashtagsManager->get(['limit' => 1])
            ->shouldBeCalled()
            ->willReturn([[
                'selected' => true,
                'value' => 'phpspec'
            ]]);

        $this
            ->isCompleted($user)
            ->shouldReturn(true);
    }

    public function it_should_check_if_not_completed(User $user)
    {
        $this->userHashtagsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->userHashtagsManager);

        $this->userHashtagsManager->get(['limit' => 1])
            ->shouldBeCalled()
            ->willReturn([[
                'selected' => false,
                'value' => 'phpspec'
            ]]);

        $this
            ->isCompleted($user)
            ->shouldReturn(false);
    }
}
