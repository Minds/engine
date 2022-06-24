<?php
namespace Minds\Core\Notifications\Push\Services;

use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\Notifications\Push\PushNotificationInterface;
use Minds\Core\Notifications\Push\System\Models\CustomPushNotification;
use Psr\Http\Message\ResponseInterface;

class ApnsService extends AbstractService implements PushServiceInterface
{
    /**
     * @param PushNotificationInterface $pushNotification
     * @return bool
     * @throws GuzzleException
     */
    public function send(PushNotificationInterface $pushNotification): bool
    {
        $alert = array_filter([
            'title' => $pushNotification->getTitle(),
            'body' => $pushNotification->getBody(),
        ]);
        
        if (!($pushNotification instanceof CustomPushNotification)) {
            $alert = [
                'body' => $pushNotification->getTitle() . ($pushNotification->getBody() ? ": {$pushNotification->getBody()}" : "")
            ];
        }

        $payload = [
            'aps' => [
                "mutable-content" => 1,
                'alert' => $alert,
                'badge' => $pushNotification->getUnreadCount(),
            ],
            'uri' => $pushNotification->getUri(),
            'user_guid' => $pushNotification->getDeviceSubscription()->getUserGuid(),
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
            return false;
        }
        return true;
    }

    /**
     * @param string $deviceToken
     * @param array $headers
     * @param array $body
     * @return ResponseInterface
     * @throws GuzzleException
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
