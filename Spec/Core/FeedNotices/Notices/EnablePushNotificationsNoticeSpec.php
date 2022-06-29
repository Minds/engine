<?php

namespace Spec\Minds\Core\FeedNotices\Notices;

use Minds\Core\FeedNotices\Notices\EnablePushNotificationsNotice;
use Minds\Core\Notifications\Push\Settings\Manager as PushSettingsManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class EnablePushNotificationsNoticeSpec extends ObjectBehavior
{
    /** @var PushSettingsManager */
    protected $pushSettingsManager;

    public function let(
        PushSettingsManager $pushSettingsManager
    ) {
        $this->pushSettingsManager = $pushSettingsManager;
        $this->beConstructedWith($pushSettingsManager);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(EnablePushNotificationsNotice::class);
    }

    public function it_should_get_location()
    {
        $this->getLocation()->shouldBe('inline');
    }

    public function it_should_get_key()
    {
        $this->getKey()->shouldBe('enable-push-notifications');
    }

    public function it_should_determine_if_notice_should_show(
        User $user
    ) {
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $this->pushSettingsManager->hasEnabledAll(123)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_determine_if_notice_should_NOT_show(
        User $user
    ) {
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $this->pushSettingsManager->hasEnabledAll(123)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(true);
    }

    public function it_should_return_instance_after_setting_user(User $user)
    {
        $this->setUser($user)
            ->shouldBe($this);
    }

    public function it_should_export(User $user)
    {
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $this->pushSettingsManager->hasEnabledAll(123)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->setUser($user);

        $this->export()->shouldBe([
            'key' => 'enable-push-notifications',
            'location' => 'inline',
            'should_show' => true
        ]);
    }
}
