<?php
namespace Minds\Core\Notifications\Push\Services;

use Minds\Core\Notifications\Push\PushNotification;
use GuzzleHttp;
use Psr\Http\Message\ResponseInterface;

class FcmService extends AbstractService implements PushServiceInterface
{
    /**
     * @param PushNotification $pushNotification
     * @return bool
     */
    public function send(PushNotification $pushNotification): bool
    {
        $body = [
            'data' => [
                'title' => $pushNotification->getTitle(),
                'body' => $pushNotification->getBody(),
                'group' => $pushNotification->getGroup(),
                'uri' => $pushNotification->getUri(),
                'largeIcon' => $pushNotification->getIcon(),
            ],
            'registration_ids' => [ $pushNotification->getDeviceSubscription()->getToken() ],
        ];
        $this->request($body);
        return true;
    }
    
    /**
     * @param array $body
     * @return ResponseInterface
     */
    protected function request($body): ResponseInterface
    {
        $json = $this->client->request('POST', 'https://fcm.googleapis.com/fcm/send', [
                    'headers' => [
                        'Authorization' => $this->getFirebaseApiKey(),
                    ],
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
