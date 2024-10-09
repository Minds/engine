<?php
namespace Minds\Core\Payments\Stripe\Webhooks\Controllers;

use Minds\Core\Config\Config;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Payments\Stripe\Webhooks\Services\SubscriptionsWebhookService;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class WebhookPsrController
{
    public function __construct(
        private SubscriptionsWebhookService $subscriptionsWebhookService,
        private EventsDispatcher $eventsDispatcher,
        private Config $config,
    ) {
        
    }

    /**
     * Stripe will call this endpoint as a generic webhook listener.
     */
    public function onWebhook(ServerRequestInterface $request): JsonResponse
    {
        $payload = $request->getBody()->getContents();
        $signature = $request->getHeader("STRIPE-SIGNATURE")[0] ?? null;

        $webhook = $this->subscriptionsWebhookService->buildWebhookEvent(
            payload: $payload,
            signature: $signature,
            secret: $this->config->get('payments')['stripe']['webhook_secret'],
        );

        $this->eventsDispatcher->trigger('webhook', 'stripe', [
            'event' => $webhook
        ]);

        return new JsonResponse([]);
    }
}
