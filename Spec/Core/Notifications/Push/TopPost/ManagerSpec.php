<?php

namespace Spec\Minds\Core\Notifications\Push\TopPost;

use Minds\Common\Repository\Response;
use Minds\Core\Feeds\FeedSyncEntity;
use Minds\Core\Feeds\UnseenTopFeed\Manager as UnseenTopFeedManager;
use Minds\Core\Notifications\NotificationTypes;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Notifications\Push\System\Builders\TopPostPushNotificationBuilder;
use Minds\Core\Notifications\Push\System\Manager as PushManager;
use Minds\Core\Notifications\Push\System\Models\CustomPushNotification;
use Minds\Core\Notifications\Push\TopPost\Manager;
use Minds\Entities\Activity;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var UnseenTopFeedManager */
    protected $unseenTopFeedManager;

    /** @var PushManager */
    protected $pushManager;

    /** @var TopPostPushNotificationBuilder */
    protected $notificationBuilder;

    public function let(
        UnseenTopFeedManager $unseenTopFeedManager,
        PushManager $pushManager,
        TopPostPushNotificationBuilder $notificationBuilder
    ) {
        $this->unseenTopFeedManager = $unseenTopFeedManager;
        $this->pushManager = $pushManager;
        $this->notificationBuilder = $notificationBuilder;

        $this->beConstructedWith(
            $unseenTopFeedManager,
            $pushManager,
            $notificationBuilder
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_send_a_single_notification(
        DeviceSubscription $deviceSubscription,
        FeedSyncEntity $feedSyncEntity,
        Activity $activity,
        Response $response,
        CustomPushNotification $customPushNotification
    ) {
        $userGuid = '123';

        $deviceSubscription->getUserGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $feedSyncEntity->getEntity()
            ->shouldBeCalled()
            ->willReturn($activity);

        $response->first()
            ->shouldBeCalled()
            ->willReturn($feedSyncEntity);

        $this->unseenTopFeedManager->getList($userGuid, 1, Argument::type('int'), excludeSelf: true)
            ->shouldBeCalled()
            ->willReturn($response);

        $customPushNotification->setDeviceSubscription($deviceSubscription)
            ->shouldBeCalled()
            ->willReturn($customPushNotification);

        $this->notificationBuilder->withEntity($activity)
            ->shouldBeCalled()
            ->willReturn($this->notificationBuilder);

        $this->notificationBuilder->build()
            ->shouldBeCalled()
            ->willReturn($customPushNotification);

        $this->pushManager->sendNotification($customPushNotification, NotificationTypes::GROUPING_TYPE_TOP_POSTS)
            ->shouldBeCalled();

        $this->sendSingle($deviceSubscription);
    }

    public function it_should_throw_an_exception_if_no_unseen_post_found_for_notification(
        DeviceSubscription $deviceSubscription,
        Activity $activity,
        Response $response,
    ) {
        $userGuid = '123';

        $deviceSubscription->getUserGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $response->first()
            ->shouldBeCalled()
            ->willReturn(null);

        $this->unseenTopFeedManager->getList($userGuid, 1, Argument::type('int'), excludeSelf: true)
            ->shouldBeCalled()
            ->willReturn($response);

        $this->notificationBuilder->build($activity)
            ->shouldNotBeCalled();

        $this->shouldThrow(ServerErrorException::class)
            ->during('sendSingle', [$deviceSubscription]);
    }
}
