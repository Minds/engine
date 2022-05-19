<?php

namespace Minds\Core\Notifications\Push\DailyDigest;

use Minds\Core\Di\Di;
use Minds\Core\Feeds\UnseenTopFeed\Manager as UnseenTopFeedManager;
use Minds\Core\Notifications\Push\System\Manager as PushManager;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Notifications\Push\System\Builders\DailyDigestPushNotificationBuilder;
use Minds\Exceptions\ServerErrorException;

/**
 * Manager for Daily Digest push notification - a notification containing
 * information from a single post from the users unseen top feed.
 */
class Manager
{
    /**
     * Constructor
     * @param ?UnseenTopFeedManager $unseenTopFeedManager - used to get an unseen post.
     * @param ?PushManager $pushManager - used to send the notification.
     * @param ?DailyDigestPushNotificationBuilder $notificationBuilder - notification builder class.
     */
    public function __construct(
        private ?UnseenTopFeedManager $unseenTopFeedManager = null,
        private ?PushManager $pushManager = null,
        private ?DailyDigestPushNotificationBuilder $notificationBuilder = null
    ) {
        $this->unseenTopFeedManager ??= Di::_()->get('Feeds\UnseenTopFeed\Manager');
        $this->pushManager ??= Di::_()->get('Notifications\Push\System\Manager');
        $this->notificationBuilder ??= new DailyDigestPushNotificationBuilder();
    }

    /**
     * Send a single notification to a given user guid and device subscription.
     * @param string $userGuid - user guid to get unseen post for.
     * @param DeviceSubscription $deviceSubscription - device subscription to send to.
     * @return void
     */
    public function sendSingle(string $userGuid, DeviceSubscription $deviceSubscription): void
    {
        $entityResponse = $this->unseenTopFeedManager->getList($userGuid, 1);

        if (!$entityResponse->first() || !$entityResponse->first()->getEntity()) {
            throw new ServerErrorException('Unable to find post for this user');
        }

        $pushNotification = $this->notificationBuilder
            ->build($entityResponse->first()->getEntity())
            ->setDeviceSubscription($deviceSubscription);

        $this->pushManager->sendNotification($pushNotification);
    }
}
