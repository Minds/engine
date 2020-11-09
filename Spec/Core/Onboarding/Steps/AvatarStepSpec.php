<?php

namespace Spec\Minds\Core\Onboarding\Steps;

use Minds\Core\Onboarding\Steps\AvatarStep;
use Minds\Entities\User;
use Minds\Core\Config;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class AvatarStepSpec extends ObjectBehavior
{
    private $config;

    public function let(Config $config)
    {
        $this->beConstructedWith($config);
        $this->config = $config;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(AvatarStep::class);
    }

    public function it_should_check_if_completed(User $user)
    {
        $this->config->get('onboarding_modal_timestamp')
            ->shouldBeCalled()
            ->willReturn(400000);
        
        $user->get('time_created')
            ->shouldBeCalled()
            ->willReturn(500000);

        $user->getLastAvatarUpload()
            ->shouldBeCalled()
            ->willReturn(500001);

        $this
            ->isCompleted($user)
            ->shouldReturn(true);
    }

    public function it_should_check_if_not_completed(User $user)
    {
        $this->config->get('onboarding_modal_timestamp')
            ->shouldBeCalled()
            ->willReturn(400000);
       
        $user->get('time_created')
            ->shouldBeCalled()
            ->willReturn(500000);

        $user->getLastAvatarUpload()
            ->shouldBeCalled()
            ->willReturn(500000);

        $this
            ->isCompleted($user)
            ->shouldReturn(false);
    }

    public function it_should_assume_that_legacy_users_have_avatars(User $user)
    {
        $this->config->get('onboarding_modal_timestamp')
            ->shouldBeCalled()
            ->willReturn(600000);

        $user->get('time_created')
            ->shouldBeCalled()
            ->willReturn(500000);

        $user->getLastAvatarUpload()
            ->shouldBeCalled()
            ->willReturn(0);

        $this
            ->isCompleted($user)
            ->shouldReturn(true);
    }
}
