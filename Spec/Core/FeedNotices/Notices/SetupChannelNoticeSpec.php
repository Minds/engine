<?php

namespace Spec\Minds\Core\FeedNotices\Notices;

use Minds\Core\FeedNotices\Notices\SetupChannelNotice;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class SetupChannelNoticeSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(SetupChannelNotice::class);
    }

    public function it_should_get_location()
    {
        $this->getLocation()->shouldBe('inline');
    }

    public function it_should_get_key()
    {
        $this->getKey()->shouldBe('setup-channel');
    }

    public function it_should_get_whether_notice_is_dismissible()
    {
        $this->isDismissible()->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_show_because_no_name(
        User $user
    ) {
        $user->getName()
            ->shouldBeCalled()
            ->willReturn('');

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_show_because_no_description(
        User $user
    ) {
        $user->getName()
            ->shouldBeCalled()
            ->willReturn('123');

        $user->get('briefdescription')
            ->shouldBeCalled()
            ->willReturn('');

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_NOT_show(
        User $user
    ) {
        $user->getName()
            ->shouldBeCalled()
            ->willReturn('123');
        
        $user->get('briefdescription')
            ->shouldBeCalled()
            ->willReturn('321');
    
        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_return_instance_after_setting_user(User $user)
    {
        $this->setUser($user)
            ->shouldBe($this);
    }

    public function it_should_export(User $user)
    {
        $user->getName()
            ->shouldBeCalled()
            ->willReturn('123');

        $user->get('briefdescription')
            ->shouldBeCalled()
            ->willReturn('321');

        $this->setUser($user);

        $this->export()->shouldBe([
            'key' => 'setup-channel',
            'location' => 'inline',
            'should_show' => false,
            'is_dismissible' => true
        ]);
    }
}
