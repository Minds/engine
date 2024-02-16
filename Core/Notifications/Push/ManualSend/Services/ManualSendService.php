<?php
declare(strict_types=1);

namespace Minds\Core\Notifications\Push\ManualSend\Services;

use Minds\Core\Log\Logger;
use Minds\Core\Notifications\Push\ManualSend\Enums\PushNotificationPlatformEnum;
use Minds\Core\Notifications\Push\ManualSend\Interfaces\ManualSendServiceInterface;
use Minds\Core\Notifications\Push\ManualSend\Models\ManualSendRequest;
use Minds\Core\Notifications\Push\Services\ApnsService;
use Minds\Core\Notifications\Push\Services\FcmService;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;

/**
 * Service for handling manually sending push notifications.
 */
class ManualSendService implements ManualSendServiceInterface
{
    public function __construct(
        private FcmService $fcmService,
        private ApnsService $apnsService,
        private Logger $logger
    ) {
    }
  
    /**
     * Send a manual push notification
     * @param ManualSendRequest $request - request containing data used to send.
     * @return bool true on success.
     */
    public function send(ManualSendRequest $request): bool
    {
        switch($request->platform) {
            case PushNotificationPlatformEnum::ANDROID:
                return $this->sendFcmRequest($request);
                break;
            case PushNotificationPlatformEnum::IOS:
                return $this->sendApnsRequest($request);
                break;
            default:
                throw new UserErrorException('Unsupported platform requested');
        }
    }

    /**
     * Send a request via FCM.
     * @param ManualSendRequest $request - request containing data used to send.
     * @return bool true on success.
     */
    private function sendFcmRequest(ManualSendRequest $request): bool
    {
        $body = [
          'message' => [
              'data' => [
                  'title' => $request->title,
                  'body' => $request->body,
                  'tag' => null,
                  'uri' => $request->uri,
                  'largeIcon' => $request->iconUrl,
                  'bigPicture' => $request->mediaUrl,
                  'badge' => '',
                  'user_guid' => $request->userGuid,
                  'metadata' => json_encode($request->metadata)
              ],
              'token' => $request->token,
          ],
        ];
    
        $response = json_decode($this->fcmService->request($body)->getBody()->getContents(), true);
        if ($response['error']) {
          $this->logger->error($response['error']);
          throw new ServerErrorException('An unexpected error has occurred');
        };
        return true;
    }

    /**
     * Send a request via APNS.
     * @param ManualSendRequest $request - request containing data used to send.
     * @return bool true on success.
     */
    private function sendApnsRequest(ManualSendRequest $request): bool
    {
        $payload = [
            'aps' => [
                "mutable-content" => 1,
                'alert' => [
                    'title' => $request->title,
                    'body' => $request->body,
                ],
                'badge' => '',
            ],
            'uri' => $request->uri,
            'user_guid' => $request->userGuid,
            'largeIcon' => $request->iconUrl,
            'metadata' => json_encode($request->metadata)
        ];
    
        try {
          $this->apnsService->request($request->token, [], $payload);
          return true;
        } catch (\Exception $e) {
            $this->logger->error($e);
            throw new ServerErrorException('An unexpected error has occurred');
        }
    }
}
