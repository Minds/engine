<?php

namespace Minds\Core\Notifications\Push\TopPost;

use Exception;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\UnseenTopFeed\Manager as UnseenTopFeedManager;
use Minds\Core\Notifications\NotificationTypes;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Notifications\Push\System\Builders\TopPostPushNotificationBuilder;
use Minds\Core\Notifications\Push\System\Manager as PushManager;
use Minds\Core\Notifications\Push\UndeliverableException;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\SkippingException;
use Minds\Exceptions\UserErrorException;

/**
 * Manager for top post push notification - a notification containing
 * information from a single post from the users unseen top feed.
 */
class Manager
{
    /**
     * Constructor
     * @param ?UnseenTopFeedManager $unseenTopFeedManager - used to get an unseen post.
     * @param ?PushManager $pushManager - used to send the notification.
     * @param ?TopPostPushNotificationBuilder $notificationBuilder - notification builder class.
     */
    public function __construct(
        private ?UnseenTopFeedManager $unseenTopFeedManager = null,
        private ?PushManager $pushManager = null,
        private ?TopPostPushNotificationBuilder $notificationBuilder = null
    ) {
        $this->unseenTopFeedManager ??= Di::_()->get('Feeds\UnseenTopFeed\Manager');
        $this->pushManager ??= Di::_()->get('Notifications\Push\System\Manager');
        $this->notificationBuilder ??= new TopPostPushNotificationBuilder();
    }

    /**
     * Send a single notification to a given device subscription.
     * @param DeviceSubscription $deviceSubscription - device subscription to send to.
     * @return void
     * @throws ServerErrorException
     * @throws UndeliverableException
     * @throws SkippingException
     * @throws UserErrorException
     */
    public function sendSingle(DeviceSubscription $deviceSubscription): void
    {
        $entityResponse = $this->unseenTopFeedManager->getList(
            userGuid: $deviceSubscription->getUserGuid(),
            limit: 1,
            fromTimestamp: strtotime('24 hours ago') * 1000,
            excludeSelf: true,
        );

        if (!$entityResponse->first() || !$entityResponse->first()->getEntity()) {
            throw new ServerErrorException('Unable to find post for this user');
        }

        $pushNotification = $this->notificationBuilder
            ->withEntity($entityResponse->first()->getEntity())
            ->build()
            ->setDeviceSubscription($deviceSubscription);

        try {
            $this->pushManager->sendNotification($pushNotification, NotificationTypes::GROUPING_TYPE_TOP_POSTS);
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }
}
