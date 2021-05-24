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
        $body = [
            'aps' => [
                'alert' => [
                    'title' => $pushNotification->getTitle(),
                    'body' => $pushNotification->getBody(),
                ],
                'badge' => $pushNotification->getUnreadCount(),
                'url-args' => [
                    $pushNotification->getUri(),
                ],
            ],
        ];

        $headers = [
            'apns-collapse-id' => $pushNotification->getMergeKey(),
        ];

        $this->request($pushNotification->getDeviceSubscription()->getToken(), $headers, $body);
        return true;
    }
    
    /**
     * @param string $deviceToken
     * @param array $body
     * @return ResponseInterface
     */
    protected function request($deviceToken, array $headers, array $body): ResponseInterface
    {
        $json = $this->client->request('POST', 'https://api.push.apple.com/3/device/' . $deviceToken, [
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
