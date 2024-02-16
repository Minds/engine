<?php
declare(strict_types=1);

namespace Minds\Core\Notifications\Push\ManualSend\Controllers;

use Minds\Core\Notifications\Push\ManualSend\Interfaces\ManualSendControllerInterface;
use Minds\Core\Notifications\Push\ManualSend\Interfaces\ManualSendPayloadValidatorInterface;
use Minds\Core\Notifications\Push\ManualSend\Interfaces\ManualSendServiceInterface;
use Minds\Core\Notifications\Push\ManualSend\Enums\PushNotificationPlatformEnum;
use Minds\Core\Notifications\Push\ManualSend\Models\ManualSendRequest;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class ManualSendController implements ManualSendControllerInterface
{
    public function __construct(
        private ManualSendServiceInterface $service,
        private ManualSendPayloadValidatorInterface $payloadValidator
    ) {
    }

    /**
     * Request to manually send a push notification.
     * @param ServerRequestInterface $request - server request.
     * @return JsonResponse true on success.
     */
    public function send(ServerRequestInterface $request): JsonResponse
    {
        $payload = $request->getParsedBody();

        if (!$this->payloadValidator->validate($payload)) {
            throw new UserErrorException(
                "There were some errors validating the request body.",
                400,
                $this->payloadValidator->getErrors()
            );
        }

        $success = $this->service->send(
            new ManualSendRequest(
                platform: PushNotificationPlatformEnum::from($payload['platform']),
                token: $payload['token'],
                title: $payload['title'] ?? 'Test notificaiton',
                body: $payload['body'] ?? 'This notification is just a test',
                uri: $payload['uri'] ?? null,
                iconUrl: $payload['icon_url'] ?? null,
                mediaUrl: $payload['media_url'] ?? null,
            )
        );

        return new JsonResponse([
            'status' => $success ? 'success' : 'error'
        ]);
    }
}
