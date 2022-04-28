<?php
namespace Minds\Core\Notifications\Push\Services;

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Minds\Core\Notifications\Push\PushNotification;
use GuzzleHttp;
use Minishlink\WebPush\MessageSentReport;
use Psr\Http\Message\ResponseInterface;

class WebPushService extends AbstractService implements PushServiceInterface
{
    /**
     * @param PushNotification $pushNotification
     * @return bool
     */
    public function send(PushNotification $pushNotification): bool
    {
        $message = $pushNotification->getTitle();
        $body = $pushNotification->getBody();

        $payload = [
            'title' => "$message",
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
        }
        return true;
    }
    
    /**
     * @param string $deviceToken
     * @param array $notification
     * @return MessageSentReport
     */
    protected function sendNotification($deviceToken, array $notification): MessageSentReport
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
        * @var MessageSentReport $report
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
     * @return string vapid details
     */
    private function getVapidDetails()
    {
        $vapidDetails = $this->config->get("webpush_vapid_details");

        return [
            'subject' => $vapidDetails['subject'],
            'publicKey' => $vapidDetails['public_key'],
            'privateKey' => $vapidDetails['private_key'],
        ];
    }
}
