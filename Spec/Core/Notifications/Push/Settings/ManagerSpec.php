<?php

namespace Spec\Minds\Core\Notifications\Push\Settings;

use Minds\Core\Notifications\Notification;
use Minds\Core\Notifications\NotificationTypes;
use Minds\Core\Notifications\Push\PushNotification;
use Minds\Core\Notifications\Push\Settings\Manager;
use Minds\Core\Notifications\Push\Settings\PushSetting;
use Minds\Core\Notifications\Push\Settings\Repository;
use Minds\Core\Notifications\Push\Settings\SettingsListOpts;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    protected Collaborator $repositoryMock;
    protected Collaborator $notificationTypesMock;

    public function let(Repository $repositoryMock, NotificationTypes $notificationTypesMock)
    {
        $this->beConstructedWith($repositoryMock, $notificationTypesMock);
        $this->repositoryMock = $repositoryMock;
        $this->notificationTypesMock = $notificationTypesMock;

        $this->notificationTypesMock->getTypesGroupings()
            ->willReturn(NotificationTypes::TYPES_GROUPINGS);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_list()
    {
        $this->repositoryMock->getList(Argument::that(function ($opts) {
            return true;
        }))
            ->willReturn([
                (new PushSetting)
                    ->setNotificationGroup('all')
                    ->setEnabled(false)
            ]);
        
        $opts = new SettingsListOpts();
        $opts->setUserGuid('123');
        
        $settings = $this->getList($opts);
        $settings[0]->getEnabled()->shouldBe(false);

        $settings->shouldHaveCount(count(NotificationTypes::TYPES_GROUPINGS) + 1);
    }

    public function it_should_not_send_push_if_all_disabled(PushNotification $pushNotification, Notification $notification)
    {
        $pushNotification->getNotification()
            ->willReturn($notification);

        $pushNotification->getGroup()
            ->willReturn(NotificationTypes::GROUPING_TYPE_VOTES);

        $notification->getToGuid()
            ->willReturn('123');

        $this->repositoryMock->getList(Argument::that(function ($opts) {
            return $opts->getUserGuid() === '123';
        }))
        ->willReturn([
                (new PushSetting)
                    ->setNotificationGroup('all')
                    ->setEnabled(false),
                (new PushSetting)
                    ->setNotificationGroup(NotificationTypes::GROUPING_TYPE_VOTES)
                    ->setEnabled(true)
            ]);
        
        $this->canSend($pushNotification)->shouldBe(false);
    }

    public function it_should_send_push_if_group_enabled(PushNotification $pushNotification, Notification $notification)
    {
        $pushNotification->getNotification()
            ->willReturn($notification);

        $pushNotification->getGroup()
            ->willReturn(NotificationTypes::GROUPING_TYPE_VOTES);

        $notification->getToGuid()
            ->willReturn('123');

        $this->repositoryMock->getList(Argument::that(function ($opts) {
            return $opts->getUserGuid() === '123';
        }))
        ->willReturn([
                (new PushSetting)
                    ->setNotificationGroup('all')
                    ->setEnabled(true),
                (new PushSetting)
                    ->setNotificationGroup(NotificationTypes::GROUPING_TYPE_VOTES)
                    ->setEnabled(true)
            ]);
        
        $this->canSend($pushNotification)->shouldBe(true);
    }

    public function it_should_not_send_forbidden_type_for_tenant(PushNotification $pushNotification, Notification $notification)
    {
        $pushNotification->getNotification()
            ->willReturn($notification);

        $pushNotification->getGroup()
            ->willReturn(NotificationTypes::GROUPING_TYPE_BOOSTS);

        $notification->getToGuid()
            ->willReturn('123');

        $this->notificationTypesMock->getTypesGroupings()
            ->willReturn([
                NotificationTypes::GROUPING_TYPE_VOTES => NotificationTypes::GROUPING_VOTES,
                NotificationTypes::GROUPING_TYPE_TAGS => NotificationTypes::GROUPING_TAGS,
            ]);

        $this->repositoryMock->getList(Argument::that(function ($opts) {
            return $opts->getUserGuid() === '123';
        }))
        ->shouldNotBeCalled();
        
        $this->canSend($pushNotification)->shouldBe(false);
    }

    public function it_should_add_setting(PushSetting $pushSetting)
    {
        $this->repositoryMock->add($pushSetting)
            ->willReturn(true);

        $this->add($pushSetting)
            ->shouldBe(true);
    }
}
