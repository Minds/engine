<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class WebhookController
{
    public function handleWebhook(ServerRequestInterface $request): JsonResponse
    {
        return new JsonResponse("");
    }
}
