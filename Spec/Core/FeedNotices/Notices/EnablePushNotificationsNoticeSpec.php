<?php

namespace Spec\Minds\Core\FeedNotices\Notices;

use Minds\Core\Config\Config;
use Minds\Core\FeedNotices\Notices\EnablePushNotificationsNotice;
use Minds\Core\Notifications\Push\Settings\Manager as PushSettingsManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class EnablePushNotificationsNoticeSpec extends ObjectBehavior
{
    /** @var PushSettingsManager */
    protected $pushSettingsManager;

    /** @var Config */
    protected $config;

    public function let(
        PushSettingsManager $pushSettingsManager,
        Config $config
    ) {
        $this->pushSettingsManager = $pushSettingsManager;
        $this->config = $config;

        $this->beConstructedWith($pushSettingsManager, $config);
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

    public function it_should_get_whether_notice_is_dismissible()
    {
        $this->isDismissible()->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_show(
        User $user
    ) {
        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(null);

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $this->pushSettingsManager->hasEnabledAll(123)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_a_user_has_all_push_notifications_enabled(
        User $user
    ) {
        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(null);

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $this->pushSettingsManager->hasEnabledAll(123)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_this_is_a_tenant_context(
        User $user
    ) {
        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn('123');

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
            'should_show' => true,
            'is_dismissible' => true
        ]);
    }
}
