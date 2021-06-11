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
                "mutable-content" => 1,
                'alert' => [
                    'body' => $message,
                ],
                'badge' => $pushNotification->getUnreadCount(),
            ],
            'uri' => $pushNotification->getUri(),
            'largeIcon' => $pushNotification->getIcon(),
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
        $uri = "https://api.push.apple.com/3/device/";

        if ($this->config->get('apple')['sandbox']) {
            $uri = "https://api.sandbox.push.apple.com/3/device/";
        }
    
        $json = $this->client->request('POST', $uri . $deviceToken, [
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
