<?php
namespace Minds\Core\Notifications\Push\Services;

use Minds\Core\Notifications\Push\PushNotification;
use GuzzleHttp;
use Psr\Http\Message\ResponseInterface;

class OneSignalService extends AbstractService implements PushServiceInterface
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
            'headings' => [
                "en" => $message,
            ],
            'contents' => [
                "en" => $body
            ],
            'data' => [
                'title' => $pushNotification->getTitle(),
                'body' => $pushNotification->getBody(),
                'tag' => $pushNotification->getMergeKey(),
                'badge' => (string) $pushNotification->getUnreadCount(), // Has to be a string
                'user_guid' => $pushNotification->getUserGuid(),
            ],
            'url' => $pushNotification->getUri(),
            'big_picture' => $pushNotification->getMedia(),
            'chrome_web_image' => $pushNotification->getIcon(),

            // TODO: add other stuff from https://documentation.onesignal.com/reference/create-notification
        ];

        try {
            $this->request($pushNotification->getDeviceSubscription()->getToken(), $payload);
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
    protected function request($deviceToken, array $body): ResponseInterface
    {
        $uri = "https://onesignal.com/api/v1/notifications";

        $body['app_id'] = $this->getOneSignalAppId();
        $body['include_player_ids'] = [$deviceToken];
        
        $json = $this->client->request(
            'POST',
            $uri,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
            ]
        );
       
        return $json;
    }

    /**
     * @return string app id
     */
    private function getOneSignalAppId()
    {
        return "5f3f75cb-67d1-40f0-afce-f8412a9b46e1";
        // return $this->config->get('onesignal')['app_id'];
    }
}
