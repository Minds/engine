<?php

namespace Spec\Minds\Core\Notifications\Push\DailyDigest;

use Minds\Common\Repository\Response;
use Minds\Core\Feeds\FeedSyncEntity;
use PhpSpec\ObjectBehavior;
use Minds\Entities\Activity;
use Minds\Exceptions\ServerErrorException;
use Minds\Core\Notifications\Push\DailyDigest\Manager;
use Minds\Core\Feeds\UnseenTopFeed\Manager as UnseenTopFeedManager;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Notifications\Push\System\Manager as PushManager;
use Minds\Core\Notifications\Push\System\Builders\DailyDigestPushNotificationBuilder;
use Minds\Core\Notifications\Push\System\Models\CustomPushNotification;

class ManagerSpec extends ObjectBehavior
{
    /** @var UnseenTopFeedManager */
    protected $unseenTopFeedManager;

    /** @var PushManager */
    protected $pushManager;

    /** @var DailyDigestPushNotificationBuilder */
    protected $notificationBuilder;

    public function let(
        UnseenTopFeedManager $unseenTopFeedManager,
        PushManager $pushManager,
        DailyDigestPushNotificationBuilder $notificationBuilder
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

        $feedSyncEntity->getEntity()
            ->shouldBeCalled()
            ->willReturn($activity);

        $response->first()
            ->shouldBeCalled()
            ->willReturn($feedSyncEntity);

        $this->unseenTopFeedManager->getList($userGuid, 1)
            ->shouldBeCalled()
            ->willReturn($response);

        $customPushNotification->setDeviceSubscription($deviceSubscription)
            ->shouldBeCalled()
            ->willReturn($customPushNotification);

        $this->notificationBuilder->build($activity)
            ->shouldBeCalled()
            ->willReturn($customPushNotification);

        $this->pushManager->sendNotification($customPushNotification)
            ->shouldBeCalled();

        $this->sendSingle($userGuid, $deviceSubscription);
    }

    public function it_should_throw_an_exception_if_no_unseen_post_found_for_notification(
        DeviceSubscription $deviceSubscription,
        FeedSyncEntity $feedSyncEntity,
        Activity $activity,
        Response $response,
        CustomPushNotification $customPushNotification
    ) {
        $userGuid = '123';

        $response->first()
            ->shouldBeCalled()
            ->willReturn(null);

        $this->unseenTopFeedManager->getList($userGuid, 1)
            ->shouldBeCalled()
            ->willReturn($response);

        $this->notificationBuilder->build($activity)
            ->shouldNotBeCalled();

        $this->shouldThrow(ServerErrorException::class)
            ->during('sendSingle', [$userGuid, $deviceSubscription]);
    }
}
