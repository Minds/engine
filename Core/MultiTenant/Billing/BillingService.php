<?php
namespace Minds\Core\MultiTenant\Billing;

use DateTimeImmutable;
use Minds\Core\Config\Config;
use Minds\Core\Email\V2\Campaigns\Recurring\TenantTrial\TenantTrialEmailer;
use Minds\Core\Guid;
use Minds\Core\MultiTenant\AutoLogin\AutoLoginService;
use Minds\Core\MultiTenant\Billing\Types\TenantBillingType;
use Minds\Core\MultiTenant\Enums\TenantPlanEnum;
use Minds\Core\MultiTenant\Enums\TenantUserRoleEnum;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\Payments\Stripe\Checkout\Manager as StripeCheckoutManager;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductPriceService as StripeProductPriceService;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;
use Minds\Core\Payments\Stripe\Checkout\Session\Services\SessionService as StripeCheckoutSessionService;
use Minds\Core\MultiTenant\Services\TenantsService;
use Minds\Core\MultiTenant\Services\TenantUsersService;
use Minds\Core\MultiTenant\Types\TenantUser;
use Minds\Core\Payments\Checkout\Enums\CheckoutTimePeriodEnum;
use Minds\Core\Payments\Stripe\Checkout\Enums\CheckoutModeEnum;
use Minds\Core\Payments\Stripe\CustomerPortal\Services\CustomerPortalService;
use Minds\Core\Payments\Stripe\Subscriptions\Services\SubscriptionsService;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\User;
use Stripe\Price;

class BillingService
{
    public function __construct(
        private readonly StripeCheckoutManager        $stripeCheckoutManager,
        private readonly StripeProductPriceService    $stripeProductPriceService,
        private readonly StripeProductService         $stripeProductService,
        private readonly StripeCheckoutSessionService $stripeCheckoutSessionService,
        private readonly DomainService                $domainService,
        private readonly TenantsService               $tenantsService,
        private readonly TenantUsersService           $usersService,
        private readonly TenantTrialEmailer           $emailService,
        private readonly SubscriptionsService         $stripeSubscriptionsService,
        private readonly AutoLoginService             $autoLoginService,
        private readonly CustomerPortalService        $customerPortalService,
        private readonly Config                       $config,
        private readonly MultiTenantBootService       $multiTenantBootService,
    ) {
    
    }
    /**
     * Returns a url to stripe checkout service, specifically for a customer
     * who is not on Minds.
     */
    public function createExternalCheckoutLink(
        TenantPlanEnum $plan,
        CheckoutTimePeriodEnum $timePeriod,
    ): string {
        // Build out the products and their add ons based on the input
        $product = $this->stripeProductService->getProductByKey('networks:' . strtolower($plan->name));
        $productPrices = $this->stripeProductPriceService->getPricesByProduct($product->id);
        $productPrice = array_filter(iterator_to_array($productPrices->getIterator()), fn (Price $price) => $price->lookup_key === 'networks:' . strtolower($plan->name) . ":" . strtolower($timePeriod->name));

        $lineItems = [
            [
                'price' => array_pop($productPrice)->id,
                'quantity' => 1,
            ]
        ];

        $checkoutSession = $this->stripeCheckoutManager->createSession(
            mode: CheckoutModeEnum::SUBSCRIPTION,
            successUrl: "api/v3/multi-tenant/billing/external-callback?session_id={CHECKOUT_SESSION_ID}",
            cancelUrl: "https://networks.minds.com/pricing",
            lineItems: $lineItems,
            paymentMethodTypes: [
                'card',
                'us_bank_account',
            ],
            submitMessage: $timePeriod === CheckoutTimePeriodEnum::YEARLY ? "You are agreeing to a 12 month subscription that will be billed monthly." : null,
            metadata: [
                'tenant_plan' => strtoupper($plan->name),
            ],
        );

        $checkoutLink = $checkoutSession->url;
        return $checkoutLink;
    }

    /**
     * Returns a stripe checkout link for a tenant admin who is trying to upgrade their network
     */
    public function createUpgradeCheckoutLink(
        TenantPlanEnum $plan,
        CheckoutTimePeriodEnum $timePeriod,
        User $loggedInUser,
    ): string {
        /** @var Tenant */
        $tenant = $this->config->get('tenant');

        if (!$tenant) {
            throw new ForbiddenException("Can only be run on an active tenant");
        }

        $this->runWithRootConfigs(function () use (&$checkoutLink, $timePeriod, $plan, $tenant, $loggedInUser) {
            // Does a subscription exist? If so, we can't do anything yet (todo), so redirect to the networks site contact form
            if ($tenant->stripeSubscription) {
                $checkoutLink = 'https://networks.minds.com/contact-upgrade?' . http_build_query([
                    'tenant_id' => $tenant->id,
                    'plan' => $plan->name,
                    'period' => $timePeriod->value,
                    'email' => $loggedInUser->getEmail(),
                ]);
                return; // This is the return of the callback, not the class function
            }

            // Build out the products and their add ons based on the input
            $product = $this->stripeProductService->getProductByKey('networks:' . strtolower($plan->name));
            $productPrices = $this->stripeProductPriceService->getPricesByProduct($product->id);
            $productPrice = array_filter(iterator_to_array($productPrices->getIterator()), fn (Price $price) => $price->lookup_key === 'networks:' . strtolower($plan->name) . ":" . strtolower($timePeriod->name));

            $lineItems = [
                [
                    'price' => array_pop($productPrice)->id,
                    'quantity' => 1,
                ]
            ];

            $navigatableDomain = $this->domainService->buildNavigatableDomain($tenant);

            $checkoutSession = $this->stripeCheckoutManager->createSession(
                mode: CheckoutModeEnum::SUBSCRIPTION,
                successUrl: "https://$navigatableDomain/api/v3/multi-tenant/billing/upgrade-callback?session_id={CHECKOUT_SESSION_ID}",
                cancelUrl: "https://networks.minds.com/pricing",
                lineItems: $lineItems,
                paymentMethodTypes: [
                    'card',
                    'us_bank_account',
                ],
                submitMessage: $timePeriod === CheckoutTimePeriodEnum::YEARLY ? "You are agreeing to a 12 month subscription that will be billed monthly." : null,
                metadata: [
                    'tenant_id' => $tenant->id,
                    'tenant_plan' => strtoupper($plan->name),
                ],
            );

            $checkoutLink = $checkoutSession->url;
        });

        return $checkoutLink;
    }

    /**
     * Execute when a checkout session has finished, so that we can create the tenant.
     * We will return an 'auto login' link for the customer too.
     */
    public function onSuccessfulCheckout(string $checkoutSessionId): string
    {
        // Get the checkout session
        $checkoutSession = $this->stripeCheckoutSessionService->retrieveCheckoutSession($checkoutSessionId);

        // Get the subscription
        $subscription = $this->stripeSubscriptionsService->retrieveSubscription($checkoutSession->subscription);

        if (isset($subscription->metadata->tenant_id)) {
            throw new ForbiddenException("The tenant has already been setup");
        }

        $plan = TenantPlanEnum::fromString($checkoutSession->metadata['tenant_plan']);

        $email = $checkoutSession->customer_details->email;

        // Create a temporary user, so that we can send them an email
        $user = $this->createtEphemeralUser($email);

        // Create the tenant
        $tenant = $this->createTenant($plan, $user, $subscription->id);

        // Build an auto login url
        $user->guid = -1;
        $loginUrl = $this->autoLoginService->buildLoginUrlWithParamsFromTenant($tenant, $user);

        // We want to redirect back to the networks site, as we need to attach the identity
        $redirectUrl = 'https://networks.minds.com/complete-checkout?email=' . $email . '&redirectUrl=' . urlencode($loginUrl);

        // Tell stripe billing about this tenant
        $this->stripeSubscriptionsService->updateSubscription(
            subscriptionId: $checkoutSession->subscription,
            metadata: [
                'tenant_id' => $tenant->id,
                'tenant_plan' => $tenant->plan->name,
            ]
        );

        return $redirectUrl;
    }

    /**
     * Execute when an upgrade checkout session has finished.
     */
    public function onSuccessfulUpgradeCheckout(string $checkoutSessionId, User $loggedInUser): string
    {
        /** Tenant */
        $tenant = $this->config->get('tenant');

        $this->runWithRootConfigs(function () use ($checkoutSessionId, $tenant, $loggedInUser) {
            // Get the checkout session
            $checkoutSession = $this->stripeCheckoutSessionService->retrieveCheckoutSession($checkoutSessionId);

            // Get the subscription
            $subscription = $this->stripeSubscriptionsService->retrieveSubscription($checkoutSession->subscription);

            $plan = TenantPlanEnum::fromString($checkoutSession->metadata['tenant_plan']);

            $this->tenantsService->upgradeTenant($tenant, $plan, $subscription->id, $loggedInUser);
        });

        /**
         * TODO: Consider using the navigatable domain here instead if we ever need the URL returned
         * At the time of writing, the return value is not in use, so there is no need to add an additional function call.
         */
        return $this->config->get('site_url') . 'network/admin/billing';
    }

    /**
     * Returns the tenant billing key info for the existing site (active tenant)
     */
    public function getTenantBillingOverview(): TenantBillingType
    {
        /** @var Tenant */
        $tenant = $this->config->get('tenant');

        if (!$tenant) {
            throw new ForbiddenException("Tenant not available");
        }

        // If the customer doesn't have a stripe subscription, there is no billing
        // setup yet.
        if (!$tenant->stripeSubscription) {
            return new TenantBillingType(
                plan: $tenant->plan,
                period: CheckoutTimePeriodEnum::MONTHLY,
                isActive: false,
            );
        }

        $this->runWithRootConfigs(function () use (&$subscription, &$manageUrl, $tenant) {
            $subscription = $this->stripeSubscriptionsService->retrieveSubscription($tenant->stripeSubscription);

            $navigatableDomain = $this->domainService->buildNavigatableDomain($tenant);

            $manageUrl = $this->customerPortalService->createCustomerPortalSession(
                stripeCustomerId: $subscription->customer,
                redirectUrl: "https://$navigatableDomain/network/admin/billing",
            );
        });

        $amountCents = array_sum(array_map(function ($item) {
            return $item->plan->amount;
        }, $subscription->items->data));

        return new TenantBillingType(
            plan: $tenant->plan,
            period: $subscription->plan->interval === 'month' ? CheckoutTimePeriodEnum::MONTHLY : CheckoutTimePeriodEnum::YEARLY,
            isActive: true,
            manageBillingUrl: $manageUrl,
            nextBillingAmountCents: $amountCents,
            nextBillingDate: (new DateTimeImmutable)->setTimestamp($subscription->current_period_end),
            previousBillingDate: (new DateTimeImmutable)->setTimestamp($subscription->current_period_start)
        );
    }

    /**
     * Aephemeral 'fake' account that is never mADE
     */
    protected function createtEphemeralUser(string $email): User
    {
        $user = new User();
        $user->username = 'networkadmin';
        $user->setEmail($email);

        return $user;
    }

    /**
     * Creates the tenant, the root user, and sends an email to the user
     * about their new site
     */
    protected function createTenant(TenantPlanEnum $plan, User $user, string $stripeSubscription): Tenant
    {
        $tenant = $this->tenantsService->createNetwork(
            new Tenant(
                id: -1,
                ownerGuid: -1,
                plan: $plan,
                stripeSubscription: $stripeSubscription,
            )
        );

        // Generate a temorary password we will share with the customer
        $password = substr(hash('sha1', openssl_random_pseudo_bytes(256)), 0, 8);
        
        // Create the root user
        $this->usersService->createNetworkRootUser(
            networkUser: new TenantUser(
                guid: (int) Guid::build(),
                username: $user->username,
                tenantId: $tenant->id,
                role: TenantUserRoleEnum::OWNER,
                plainPassword: $password,
            ),
            sourceUser: $user
        );
 
        // Send an email with a the username and password to login to the tenant
        $this->emailService->setUser($user)
            ->setTenantId($tenant->id)
            ->setUsername($user->username)
            ->setPassword($password)
            ->setIsTrial(false)
            ->send();
 
        return $tenant;

    }

    /**
     * A helper function to ensure that code is run on the root configs and not on
     * the tenant configs.
     */
    private function runWithRootConfigs(callable $function): void
    {
        /** @var Tenant */
        $tenant = $this->config->get('tenant');

        // Rescope to root, as we need to use the Minds creds, not tenant
        $this->multiTenantBootService->resetRootConfigs();

        call_user_func($function);

        // Revert back to tenant configs
        $this->multiTenantBootService->bootFromTenantId($tenant->id);
    }
}
