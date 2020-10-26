<?php

namespace Spec\Minds\Core\Onboarding\Steps;

use Minds\Core\Onboarding\Steps\SetupChannelStep;
use Minds\Core\Config;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SetupChannelStepSpec extends ObjectBehavior
{
    private $config;

    public function let(Config $config)
    {
        $this->beConstructedWith($config);
        $this->config = $config;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SetupChannelStep::class);
    }

    public function it_should_check_if_completed(User $user)
    {
        // Avatar
        $this->config->get('onboarding_modal_timestamp')
            ->shouldBeCalled()
            ->willReturn(400000);
        
        $user->get('time_created')
            ->shouldBeCalled()
            ->willReturn(500000);

        $user->getLastAvatarUpload()
            ->shouldBeCalled()
            ->willReturn(500001);

        // Bio
        $user->get('briefdescription')
            ->shouldBeCalled()
            ->willReturn('phpspec');

        // Display name
        $user->get('name')
            ->shouldBeCalled()
            ->willReturn('phpspec');

        $this
            ->isCompleted($user)
            ->shouldReturn(true);
    }

    public function it_should_check_if_not_completed(User $user)
    {
        // Display name
        $user->get('name')
            ->shouldBeCalled()
            ->willReturn('');

        $this
            ->isCompleted($user)
            ->shouldReturn(false);
    }

    public function it_should_check_if_not_avatar_completed(User $user)
    {
        

        // Display name
        $user->get('name')
            ->shouldBeCalled()
            ->willReturn('I do have a name');

        // Avatar
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

    public function it_should_check_if_not_bio_completed(User $user)
    {
        // Display name
        $user->get('name')
            ->shouldBeCalled()
            ->willReturn('I do have a name');

        // Avatar
        $this->config->get('onboarding_modal_timestamp')
            ->shouldBeCalled()
            ->willReturn(400000);
    
        $user->get('time_created')
            ->shouldBeCalled()
            ->willReturn(500000);

        $user->getLastAvatarUpload()
            ->shouldBeCalled()
            ->willReturn(500001);

        // Bio
        $user->get('briefdescription')
            ->shouldBeCalled()
            ->willReturn('');

        $this
            ->isCompleted($user)
            ->shouldReturn(false);
    }
}
