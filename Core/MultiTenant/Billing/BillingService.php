<?php
namespace Minds\Core\MultiTenant\Billing;

use Minds\Core\Email\V2\Campaigns\Recurring\TenantTrial\TenantTrialEmailer;
use Minds\Core\Guid;
use Minds\Core\MultiTenant\AutoLogin\AutoLoginService;
use Minds\Core\MultiTenant\Enums\TenantPlanEnum;
use Minds\Core\MultiTenant\Enums\TenantUserRoleEnum;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\Payments\Stripe\Checkout\Manager as StripeCheckoutManager;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductPriceService as StripeProductPriceService;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;
use Minds\Core\Payments\Stripe\Checkout\Session\Services\SessionService as StripeCheckoutSessionService;
use Minds\Core\MultiTenant\Services\TenantsService;
use Minds\Core\MultiTenant\Services\TenantUsersService;
use Minds\Core\MultiTenant\Types\TenantUser;
use Minds\Core\Payments\Checkout\Enums\CheckoutTimePeriodEnum;
use Minds\Core\Payments\Stripe\Checkout\Enums\CheckoutModeEnum;
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
        private readonly TenantsService               $tenantsService,
        private readonly TenantUsersService           $usersService,
        private readonly TenantTrialEmailer           $emailService,
        private readonly SubscriptionsService         $stripeSubscriptionsService,
        private readonly AutoLoginService             $autoLoginService,
    ) {
    
    }
    /**
     * Returns a url to stripe checkout service, specifically for a customer
     * who is not on Minds.
     */
    public function createExternalCheckoutLink(
        string $planId,
        CheckoutTimePeriodEnum $timePeriod,
    ): string {
        // Build out the products and their add ons based on the input
        $product = $this->stripeProductService->getProductByKey($planId);
        $productPrices = $this->stripeProductPriceService->getPricesByProduct($product->id);
        $productPrice = array_filter(iterator_to_array($productPrices->getIterator()), fn (Price $price) => $price->lookup_key === $planId . ":" . strtolower($timePeriod->name));

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
                'tenant_plan' => strtoupper(str_replace('networks:', '', $planId)),
                'isTrialUpgrade' => 'false',
            ],
        );

        $checkoutLink = $checkoutSession->url;
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
        $tenant = $this->createTenant($plan, $user);

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
     * Aephemeral 'fake' account that is never mADE
     */
    protected function createtEphemeralUser(string $email): User
    {
        $user = new User();
        $user->username = 'networkadmin';
        $user->setEmail($email);

        return $user;
    }

    protected function createTenant(TenantPlanEnum $plan, User $user): Tenant
    {
        $tenant = $this->tenantsService->createNetwork(
            new Tenant(
                id: -1,
                ownerGuid: -1,
                plan: $plan,
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
}
