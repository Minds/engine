<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Billing;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Email\V2\Campaigns\Recurring\TenantTrial\TenantTrialEmailer;
use Minds\Core\EventStreams\Topics\TenantBootstrapRequestsTopic;
use Minds\Core\MultiTenant\AutoLogin\AutoLoginService;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\MultiTenant\Services\TenantsService;
use Minds\Core\MultiTenant\Services\TenantUsersService;
use Minds\Core\Payments\Stripe\Checkout\Manager as StripeCheckoutManager;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductPriceService as StripeProductPriceService;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;
use Minds\Core\Payments\Stripe\Checkout\Session\Services\SessionService as StripeCheckoutSessionService;
use Minds\Core\Payments\Stripe\CustomerPortal\Services\CustomerPortalService;
use Minds\Core\Payments\Stripe\Subscriptions\Services\SubscriptionsService;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind(BillingService::class, function ($di) {
            return new BillingService(
                stripeCheckoutManager: $di->get(StripeCheckoutManager::class),
                stripeProductPriceService: $di->get(StripeProductPriceService::class),
                stripeProductService: $di->get(StripeProductService::class),
                stripeCheckoutSessionService: $di->get(StripeCheckoutSessionService::class),
                domainService: $di->get(DomainService::class),
                tenantsService: $di->get(TenantsService::class),
                usersService: $di->get(TenantUsersService::class),
                emailService: new TenantTrialEmailer(),
                tenantBootstrapRequestsTopic: $di->get(TenantBootstrapRequestsTopic::class),
                stripeSubscriptionsService: $di->get(SubscriptionsService::class),
                autoLoginService: $di->get(AutoLoginService::class),
                customerPortalService: $di->get(CustomerPortalService::class),
                config: $di->get(Config::class),
                multiTenantBootService: $di->get(MultiTenantBootService::class),
            );
        });
        
        $this->di->bind(Controllers\BillingPsrController::class, function (Di $di) {
            return new Controllers\BillingPsrController(
                service: $di->get(BillingService::class),
            );
        });

        $this->di->bind(Controllers\BillingGqlController::class, function (Di $di) {
            return new Controllers\BillingGqlController(
                service: $di->get(BillingService::class),
            );
        });
    }
}
