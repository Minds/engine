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
            'message' => [
                'notification' => [
                    'title' =>  $pushNotification->getTitle(),
                    'body' => $pushNotification->getBody(),
                    'image' => $pushNotification->getIcon(),
                ],
                'android' => [
                    'collapse_key' => $pushNotification->getMergeKey(),
                    'notification' => [
                        'title' =>  $pushNotification->getTitle(),
                        'body' => $pushNotification->getBody(),
                        'tag' => $pushNotification->getMergeKey(),
                        'image' => $pushNotification->getMedia(),
                        'icon' => $pushNotification->getIcon(),
                        'default_sound' => true,
                        'default_vibrate_timings' => true,
                        'notification_count' =>  $pushNotification->getUnreadCount(),
                    ]
                ],
                'data' => [
                    'uri' => $pushNotification->getUri(),
                    'largeIcon' => $pushNotification->getIcon(),
                    'bigPicture' => $pushNotification->getMedia(),
                ],
                'token' => $pushNotification->getDeviceSubscription()->getToken(),
            ],
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
        $client = new \Google_Client();
        $client->setAuthConfig($this->getFirebaseKey());
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $httpClient = $client->authorize();

        $projectId = $this->getFirebaseProjectId();

        $json = $httpClient->request('POST', "https://fcm.googleapis.com/v1/projects/$projectId/messages:send", [
                    'json' => $body
                ]);
        
        return $json;
    }

    /**
     * @return string
     */
    protected function getFirebaseProjectId(): string
    {
        return $this->config->get('google')['firebase']['project_id'];
    }

    /**
     * @return string
     */
    protected function getFirebaseKey(): string
    {
        return $this->config->get('google')['firebase']['key_path'];
    }

}
