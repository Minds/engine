<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Checkout\Services;

use Minds\Core\MultiTenant\Cache\MultiTenantCacheHandler;
use Minds\Core\MultiTenant\Enums\TenantPlanEnum;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\TenantsService;
use Minds\Core\Payments\Checkout\Delegates\CheckoutEventsDelegate;
use Minds\Core\Payments\Checkout\Enums\CheckoutTimePeriodEnum;
use Minds\Core\Payments\Stripe\Checkout\Enums\CheckoutModeEnum;
use Minds\Core\Payments\Stripe\Checkout\Manager as StripeCheckoutManager;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductSubTypeEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductTypeEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductPriceService as StripeProductPriceService;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;
use Minds\Core\Payments\Stripe\Checkout\Session\Services\SessionService as StripeCheckoutSessionService;
use Minds\Core\Payments\Stripe\Subscriptions\Services\SubscriptionsService;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Stripe\Exception\ApiErrorException;
use Stripe\Price;
use Stripe\Product;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class CheckoutService
{
    private const CACHE_TTL = 60 * 5; // 5 minutes

    public function __construct(
        private readonly StripeCheckoutManager        $stripeCheckoutManager,
        private readonly StripeProductPriceService    $stripeProductPriceService,
        private readonly StripeProductService         $stripeProductService,
        private readonly StripeCheckoutSessionService $stripeCheckoutSessionService,
        private readonly TenantsService               $tenantsService,
        private readonly SubscriptionsService         $stripeSubscriptionsService,
        private readonly CacheInterface               $cache,
        private readonly CheckoutEventsDelegate       $checkoutEventsDelegate,
        private readonly MultiTenantCacheHandler      $multiTenantCacheHandler
    ) {
    }

    /**
     * @param User $user
     * @param string $planId
     * @param CheckoutTimePeriodEnum $timePeriod
     * @param string[]|null $addOnIds
     * @return string
     * @throws GraphQLException
     * @throws ServerErrorException
     * @throws InvalidArgumentException
     * @throws ApiErrorException
     */
    public function generateCheckoutLink(
        User                   $user,
        string                 $planId,
        CheckoutTimePeriodEnum $timePeriod,
        ?array                 $addOnIds,
        bool                   $isTrialUpgrade = false
    ): string {
        $lineItems = [];

        $product = $this->prepareCheckoutProductLineItems(
            planId: $planId,
            timePeriod: $timePeriod,
            lineItems: $lineItems
        );

        $this->prepareCheckoutAddonsLineItems(
            productType: ProductTypeEnum::tryFrom($product->metadata['type']),
            addOnIds: $addOnIds,
            lineItems: $lineItems
        );

        $checkoutSession = $this->stripeCheckoutManager->createSession(
            user: $user,
            mode: CheckoutModeEnum::SUBSCRIPTION,
            successUrl: "api/v3/payments/checkout/complete?session_id={CHECKOUT_SESSION_ID}",
            cancelUrl: "networks/checkout?planId=$planId&timePeriod=" . strtolower($timePeriod->name),
            lineItems: $lineItems,
            paymentMethodTypes: [
                'card',
                'us_bank_account',
            ],
            submitMessage: $timePeriod === CheckoutTimePeriodEnum::YEARLY ? "You are agreeing to a 12 month subscription that will be billed monthly." : null,
            metadata: [
                'tenant_plan' => strtoupper(str_replace('networks:', '', $planId)),
                'isTrialUpgrade' => $isTrialUpgrade ? 'true' : 'false',
            ]
        );

        $this->cache->set(
            "checkout_session_{$user->getGuid()}",
            json_encode([
                'user_guid' => $user->getGuid(),
                'plan_id' => $planId,
                'time_period' => $timePeriod->value,
                'add_on_ids' => $addOnIds,
            ]),
            self::CACHE_TTL
        );

        $this->checkoutEventsDelegate->sendCheckoutPaymentEvent(
            user: $user,
            productId: $planId,
            timePeriod: $timePeriod,
            addonIds: $addOnIds
        );

        return $checkoutSession->url;
    }

    /**
     * @param string $planId
     * @param CheckoutTimePeriodEnum $timePeriod
     * @param array $lineItems
     * @return Product
     * @throws ApiErrorException
     * @throws GraphQLException
     */
    private function prepareCheckoutProductLineItems(
        string                 $planId,
        CheckoutTimePeriodEnum $timePeriod,
        array                  &$lineItems
    ): Product {
        try {
            $product = $this->stripeProductService->getProductByKey($planId);
            $productPrices = $this->stripeProductPriceService->getPricesByProduct($product->id);
        } catch (NotFoundException $e) {
            throw new GraphQLException($e->getMessage(), 404);
        } catch (ServerErrorException $e) {
            throw new GraphQLException($e->getMessage(), 500);
        }

        $productPrice = array_filter(iterator_to_array($productPrices->getIterator()), fn (Price $price) => $price->lookup_key === $planId . ":" . strtolower($timePeriod->name));

        $lineItems[] = [
            'price' => array_pop($productPrice)->id,
            'quantity' => 1,
        ];

        return $product;
    }

    /**
     * @param ProductTypeEnum $productType
     * @param array $addOnIds
     * @param array $lineItems
     * @return void
     * @throws GraphQLException
     * @throws InvalidArgumentException
     * @throws ServerErrorException
     */
    private function prepareCheckoutAddonsLineItems(
        ProductTypeEnum $productType,
        array           $addOnIds,
        array           &$lineItems
    ): void {
        try {
            $productAddons = $this->stripeProductService->getProductsByType(
                productType: $productType,
                productSubType: ProductSubTypeEnum::ADDON
            );
        } catch (NotFoundException $e) {
            throw new GraphQLException($e->getMessage(), 404);
        }

        $checkoutAddons = array_filter(iterator_to_array($productAddons->getIterator()), fn (Product $addon) => in_array($addon->metadata['key'], $addOnIds ?? [], true));

        foreach ($checkoutAddons as $addon) {
            $addonPrices = $this->stripeProductPriceService->getPricesByProduct($addon->id);
            /**
             * @var Price $addonPrice
             */
            foreach ($addonPrices->getIterator() as $addonPrice) {
                $details = [
                    'price' => $addonPrice->id,
                    'quantity' => 1,
                ];

                if ($addonPrice->recurring?->usage_type === 'metered') {
                    unset($details['quantity']);
                }

                $lineItems[] = $details;
            }
        }

    }

    /**
     * @param User $user
     * @param string $stripeCheckoutSessionId
     * @return void
     * @throws ApiErrorException
     * @throws GraphQLException
     * @throws ServerErrorException
     */
    public function completeCheckout(User $user, string $stripeCheckoutSessionId): void
    {
        $checkoutSession = $this->stripeCheckoutSessionService->retrieveCheckoutSession($stripeCheckoutSessionId);

        $plan = TenantPlanEnum::fromString($checkoutSession->metadata['tenant_plan']);

        $tenant = $this->tenantsService->createNetwork(
            tenant: new Tenant(
                id: 0,
                ownerGuid: (int)$user->getGuid(),
                plan: $plan,
            )
        );

        $this->stripeSubscriptionsService->updateSubscription(
            subscriptionId: $checkoutSession->subscription,
            metadata: [
                'tenant_id' => $tenant->id,
                'tenant_plan' => $tenant->plan->name,
            ]
        );
    }
}
