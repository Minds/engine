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
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    public function let(Repository $repository)
    {
        $this->beConstructedWith($repository);
        $this->repository = $repository;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_list()
    {
        $this->repository->getList(Argument::that(function ($opts) {
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

    public function it_should_confirm_if_we_can_send_push(PushNotification $pushNotification, Notification $notification)
    {
        $pushNotification->getNotification()
            ->willReturn($notification);

        $pushNotification->getGroup()
            ->willReturn(NotificationTypes::GROUPING_TYPE_VOTES);

        $notification->getToGuid()
            ->willReturn('123');

        $this->repository->getList(Argument::that(function ($opts) {
            return $opts->getUserGuid() === '123';
        }))
        ->willReturn([
                (new PushSetting)
                    ->setNotificationGroup('all')
                    ->setEnabled(false)
            ]);
        
        $this->canSend($pushNotification)->shouldBe(false);
    }

    public function it_should_add_setting(PushSetting $pushSetting)
    {
        $this->repository->add($pushSetting)
            ->willReturn(true);

        $this->add($pushSetting)
            ->shouldBe(true);
    }
}
