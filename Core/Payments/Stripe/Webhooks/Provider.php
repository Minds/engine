<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Webhooks;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\Payments\Stripe\Webhooks\Repositories\WebhooksConfigurationRepository;
use Minds\Core\Payments\Stripe\Webhooks\Services\SubscriptionsWebhookService;
use Minds\Core\Payments\Stripe\Webhooks\Services\WebhookEventBuilderService;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            SubscriptionsWebhookService::class,
            fn (Di $di): SubscriptionsWebhookService => new SubscriptionsWebhookService(
                config: $di->get(Config::class),
                webhooksConfigurationRepository: $di->get(WebhooksConfigurationRepository::class),
                webhookEventBuilderService: $di->get(WebhookEventBuilderService::class),
                domainService: $di->get(DomainService::class)
            )
        );

        $this->di->bind(
            WebhookEventBuilderService::class,
            fn (Di $di): WebhookEventBuilderService => new WebhookEventBuilderService()
        );

        $this->di->bind(
            WebhooksConfigurationRepository::class,
            fn (Di $di): WebhooksConfigurationRepository => new WebhooksConfigurationRepository(
                mysqlHandler: $di->get('Database\MySQL\Client'),
                config: $di->get(Config::class),
                logger: $di->get('Logger')
            )
        );
    }
}
