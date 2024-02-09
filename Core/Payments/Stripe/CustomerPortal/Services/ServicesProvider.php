<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\CustomerPortal\Services;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider;
use Minds\Core\Payments\Stripe\CustomerPortal\Repositories\CustomerPortalConfigurationRepository;
use Minds\Core\Payments\Stripe\StripeClient;

class ServicesProvider extends Provider
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->di->bind(
            CustomerPortalService::class,
            fn (Di $di): CustomerPortalService => new CustomerPortalService(
                stripeClient: $di->get(StripeClient::class, ['stripe_version' => '2020-08-27']),
                customerPortalConfigurationRepository: $di->get(CustomerPortalConfigurationRepository::class),
                config: $di->get(Config::class)
            )
        );
    }
}
