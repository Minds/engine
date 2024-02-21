<?php
declare(strict_types=1);

namespace Minds\Core\Notifications\Push\ManualSend\Interfaces;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Manual send push notification controller.
 */
interface ManualSendControllerInterface
{
    /**
     * Request to manually send a push notification.
     * @param ServerRequestInterface $request - server request.
     * @return JsonResponse true on success.
     */
    public function send(ServerRequestInterface $request): JsonResponse;
}
