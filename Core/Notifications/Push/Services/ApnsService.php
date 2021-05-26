<?php
namespace Minds\Core\Notifications\Push\Services;

use Minds\Core\Notifications\Push\PushNotification;
use GuzzleHttp;
use Psr\Http\Message\ResponseInterface;

class ApnsService extends AbstractService implements PushServiceInterface
{
    /**
     * @param PushNotification $pushNotification
     * @return bool
     */
    public function send(PushNotification $pushNotification): bool
    {
        $message = $pushNotification->getTitle();
        if ($body = $pushNotification->getBody()) {
            $message .= ": $body";
        }

        $payload = [
            'aps' => [
                'alert' => [
                    'body' => $message,
                ],
                'badge' => $pushNotification->getUnreadCount(),
                'url-args' => [
                    $pushNotification->getUri(),
                ],
            ],
        ];

        $headers = [
            'apns-collapse-id' => $pushNotification->getMergeKey(),
            'apns-topic' => 'com.minds.mobile',
        ];

        try {
            $this->request($pushNotification->getDeviceSubscription()->getToken(), $headers, $payload);
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }
        return true;
    }
    
    /**
     * @param string $deviceToken
     * @param array $body
     * @return ResponseInterface
     */
    protected function request($deviceToken, array $headers, array $body): ResponseInterface
    {
        $json = $this->client->request('POST', 'https://api.sandbox.push.apple.com/3/device/' . $deviceToken, [
                    'version' => 2,
                    'headers' => $headers,
                    'cert' => $this->config->get('apple')['cert'],
                    'json' => $body
                ]);
       
        return $json;
    }

    /**
     * @return string
     */
    protected function getFirebaseApiKey(): string
    {
        return $this->config->get('google')['push'];
    }
}
