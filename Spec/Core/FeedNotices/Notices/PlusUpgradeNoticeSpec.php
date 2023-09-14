<?php

namespace Spec\Minds\Core\FeedNotices\Notices;

use Minds\Core\FeedNotices\Notices\PlusUpgradeNotice;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class PlusUpgradeNoticeSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(PlusUpgradeNotice::class);
    }

    public function it_should_get_location()
    {
        $this->getLocation()->shouldBe('top');
    }

    public function it_should_get_key()
    {
        $this->getKey()->shouldBe('plus-upgrade');
    }

    public function it_should_get_whether_notice_is_dismissible()
    {
        $this->isDismissible()->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_show(
        User $user
    ) {
        $user->getAge()
            ->shouldBeCalled()
            ->willReturn(2592001);

        $user->isPlus()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_user_account_is_less_than_30_days_old(
        User $user
    ) {
        $user->getAge()
        ->shouldBeCalled()
        ->willReturn(2591999);

        $user->isPlus()
            ->shouldNotBeCalled();

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_user_account_is_already_plus(
        User $user
    ) {
        $user->getAge()
            ->shouldBeCalled()
            ->willReturn(2592001);

        $user->isPlus()
            ->shouldBeCalled()
            ->willReturn(true);

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
        $user->getAge()
            ->shouldBeCalled()
            ->willReturn(2592001);

        $user->isPlus()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->setUser($user);

        $this->export()->shouldBe([
            'key' => 'plus-upgrade',
            'location' => 'top',
            'should_show' => true,
            'is_dismissible' => true
        ]);
    }
}
