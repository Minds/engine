<?php
namespace Minds\Core\Notifications\Push\Services;

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Minds\Core\Notifications\Push\PushNotification;
use GuzzleHttp;
use Minishlink\WebPush\MessageSentReport;
use Psr\Http\Message\ResponseInterface;

class WebService extends AbstractService implements PushServiceInterface
{
    const defaultOptions = [
        'TTL' => 300, // defaults to 4 weeks
        'urgency' => 'normal', // protocol defaults to "normal"
        'topic' => 'new_event', // not defined by default,
        'batchSize' => 200, // defaults to 1000
    ];
    /**
     * @param PushNotification $pushNotification
     * @return bool
     */
    public function send(PushNotification $pushNotification): bool
    {
        $message = $pushNotification->getTitle();
        $body = $pushNotification->getBody();

        $payload = [
            'notification' => [
                'title' => "$message",
                'body' => "$body",
                'tag' => $pushNotification->getMergeKey(),
                'badge' => (string) $pushNotification->getUnreadCount(),
                'icon' => $pushNotification->getIcon(),
                'image' => $pushNotification->getMedia(),
                // 'requireInteraction' => true,
                // 'silent' => false,
                // 'timestamp' => ,
                // 'vibrate' => ,
                // 'renotify' => ,
                // 'lang' => ,
                // 'dir' => ,
                // 'data' => ,
                // 'actions' => ,
                // 'url' => $pushNotification->getUri(),
            ]
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
     * @param array $body
     * @return MessageSentReport
     */
    protected function sendNotification($deviceToken, array $body): MessageSentReport
    {
        $webPush = new WebPush([
            'VAPID' => $this->getVapidDetails(),
        ]);
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
            json_encode($body),
            [
            //     'TTL' => 300, // defaults to 4 weeks
            //     'urgency' => 'normal', // protocol defaults to "normal"
                'topic' => $body['notification']['tag'], // not defined by default,
            //     'batchSize' => 200, // defaults to 1000
            ]
        );

        // TODO: handle error

        return $report;
    }

    /**
     * @return string vapid details
     */
    private function getVapidDetails()
    {
        $vapidDetails = $this->config->get("webpush_vapid_details");

        return [
            'subject' => $vapidDetails['email'],
            'publicKey' => $vapidDetails['public_key'],
            'privateKey' => $vapidDetails['private_key'],
        ];
    }
}
