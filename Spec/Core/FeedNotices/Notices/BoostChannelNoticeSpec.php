<?php

namespace Spec\Minds\Core\FeedNotices\Notices;

use Minds\Core\FeedNotices\Notices\BoostChannelNotice;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class BoostChannelNoticeSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(BoostChannelNotice::class);
    }

    public function it_should_get_location()
    {
        $this->getLocation()->shouldBe('top');
    }

    public function it_should_get_key()
    {
        $this->getKey()->shouldBe('boost-channel');
    }

    public function it_should_get_whether_notice_is_dismissible()
    {
        $this->isDismissible()->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_show(
        User $user
    ) {
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_NOT_show(
        User $user
    ) {
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(false);

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
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setUser($user);

        $this->export()->shouldBe([
            'key' => 'boost-channel',
            'location' => 'top',
            'should_show' => true,
            'is_dismissible' => true,
        ]);
    }
}
