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
                throw new UserErrorException('Unsupported platform requested.');
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
                    'uri' => $request->uri,
                    'largeIcon' => $request->iconUrl,
                    'bigPicture' => $request->mediaUrl
                ],
                'token' => $request->token,
          ],
        ];

        $response = json_decode($this->fcmService->request($body)->getBody()->getContents(), true);
        if (!$response || isset($response['error'])) {
            $errorMessage = 'An unexpected error has occurred';
            $this->logger->error($response['error'] ?? $errorMessage);
            throw new ServerErrorException($errorMessage);
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
                    'body' => $request->body
                ],
                'badge' => '',
            ],
            'uri' => $request->uri,
            'largeIcon' => $request->iconUrl
        ];

        $response = json_decode($this->apnsService->request($request->token, [], $payload)->getBody()->getContents(), true);
        if (!$response || isset($response['error'])) {
            $errorMessage = 'An unexpected error has occurred';
            $this->logger->error($response['error'] ?? $errorMessage);
            throw new ServerErrorException($errorMessage);
        };
        return true;
    }
}
