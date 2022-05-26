<?php
namespace Minds\Core\Notifications\Push\Services;

use Google\Exception;
use Google_Client;
use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\Notifications\Push\PushNotification;
use Minds\Core\Notifications\Push\PushNotificationInterface;
use Minds\Core\Notifications\Push\UndeliverableException;
use Psr\Http\Message\ResponseInterface;

class FcmService extends AbstractService implements PushServiceInterface
{
    /** @var Google_Client */
    protected $googleClient;

    public function __construct(Google_Client $googleClient = null, ...$deps)
    {
        $this->googleClient = $googleClient ?? new Google_Client();
        parent::__construct(...$deps);
    }

    /**
     * @param PushNotification $pushNotification
     * @return bool
     * @throws Exception
     * @throws GuzzleException
     * @throws UndeliverableException
     */
    public function send(PushNotificationInterface $pushNotification): bool
    {
        $body = [
            'message' => [
                'data' => [
                    'title' => $pushNotification->getTitle(),
                    'body' => $pushNotification->getBody(),
                    'tag' => $pushNotification->getMergeKey(),
                    'uri' => $pushNotification->getUri(),
                    'largeIcon' => $pushNotification->getIcon(),
                    'bigPicture' => $pushNotification->getMedia(),
                    'badge' => (string) $pushNotification->getUnreadCount(), // Has to be a string
                    'user_guid' => $pushNotification->getUserGuid(),
                ],
                'token' => $pushNotification->getDeviceSubscription()->getToken(),
            ],
        ];
        try {
            $this->request($body);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * @param array $body
     * @return ResponseInterface
     * @throws GuzzleException
     * @throws Exception
     */
    protected function request($body): ResponseInterface
    {
        $this->googleClient->setAuthConfig($this->getFirebaseKey());
        $this->googleClient->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $httpClient = $this->googleClient->authorize();

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
