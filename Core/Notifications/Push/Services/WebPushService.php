<?php
namespace Minds\Core\Notifications\Push\Services;

use ErrorException;
use Minds\Core\Notifications\Push\PushNotificationInterface;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService extends AbstractService implements PushServiceInterface
{
    /**
     * @param PushNotificationInterface $pushNotification
     * @return bool
     */
    public function send(PushNotificationInterface $pushNotification): bool
    {
        $title = $pushNotification->getTitle() ?: ' '; // can't be blank, so insert a space char
        $body = $pushNotification->getBody();

        $payload = [
            'title' => "$title",
            'body' => "$body",
            'tag' => $pushNotification->getMergeKey(),
            'badge' => (string) $pushNotification->getUnreadCount(),
            'icon' => $pushNotification->getMedia(),
            'image' => $pushNotification->getMedia(),
            'renotify' => true,
            'requireInteraction' => false,
            'data' => [
                'onActionClick' => [
                    'default' => [
                        'operation' => 'openWindow',
                        'url' => $pushNotification->getUri()
                    ],
                ]
            ],
            // 'silent' => false,
            // 'timestamp' => ,
            // 'vibrate' => ,
            // 'lang' => ,
            // 'dir' => ,
        ];

        try {
            $this->sendNotification($pushNotification->getDeviceSubscription()->getToken(), $payload);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * @param string $deviceToken
     * @param array $notification
     * @return MessageSentReport|null
     * @throws ErrorException
     */
    protected function sendNotification($deviceToken, array $notification): ?MessageSentReport
    {
        $webPush = new WebPush([
            'VAPID' => $this->getVapidDetails(),
        ]);
        // TODO: figure out if we should do something about padding and optimization
        // $webPush->setAutomaticPadding(false);

        $pushSubscription = null;
        try {
            $pushSubscription = json_decode(base64_decode($deviceToken, true), true);
        } catch (\Exception $e) {
            // TODO: deviceToken is corrupt. Do something about it?
        }

        if (!$pushSubscription) {
            return null;
        }

        /**
        * send one notification and flush directly
        */
        $report = $webPush->sendOneNotification(
            Subscription::create($pushSubscription),
            json_encode(['notification' => $notification]),
            [
            //     'TTL' => 300, // defaults to 4 weeks
            //     'urgency' => 'normal', // protocol defaults to "normal"
                'topic' => $notification['tag'],
            //     'batchSize' => 1000,
            ]
        );

        // TODO: handle error if necessary

        return $report;
    }

    /**
     * @return array vapid details
     */
    private function getVapidDetails(): array
    {
        $vapidDetails = $this->config->get("webpush_vapid_details");

        return [
            'subject' => $vapidDetails['subject'],
            'publicKey' => $vapidDetails['public_key'],
            'privateKey' => $vapidDetails['private_key'],
        ];
    }
}
