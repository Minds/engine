<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Payments\Stripe\Webhooks\Controllers\WebhookPsrController;
use Minds\Core\Payments\Stripe\Webhooks\Services\SubscriptionsWebhookService;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        (new Checkout\Products\Services\ServicesProvider())->register();
        (new Checkout\Session\Services\ServicesProvider())->register();
        (new Subscriptions\Services\ServicesProvider())->register();
    
        $this->di->bind(
            WebhookPsrController::class,
            fn (Di $di): WebhookPsrController =>
            new WebhookPsrController(
                subscriptionsWebhookService: $di->get(SubscriptionsWebhookService::class),
                eventsDispatcher: $di->get('EventsDispatcher'),
                config: $di->get(Config::class),
            )
        );
    }
}
