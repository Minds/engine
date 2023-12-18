<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Checkout\Services;

use Minds\Core\Payments\Checkout\Enums\CheckoutPageKeyEnum;
use Minds\Core\Payments\Checkout\Enums\CheckoutTimePeriodEnum;
use Minds\Core\Payments\Checkout\Types\AddOn;
use Minds\Core\Payments\Checkout\Types\AddOnSummary;
use Minds\Core\Payments\Checkout\Types\CheckoutPage;
use Minds\Core\Payments\Checkout\Types\Plan;
use Minds\Core\Payments\Checkout\Types\PlanSummary;
use Minds\Core\Payments\Checkout\Types\Summary;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductSubTypeEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductTypeEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductPriceService as StripeProductPriceService;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;
use Minds\Core\Strapi\Services\StrapiService;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Stripe\Exception\ApiErrorException;
use Stripe\Price;
use Stripe\Product;
use Stripe\SearchResult;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class CheckoutContentService
{
    public function __construct(
        private readonly StrapiService             $strapiService,
        private readonly StripeProductService      $stripeProductService,
        private readonly StripeProductPriceService $stripeProductPriceService,
        private readonly CacheInterface            $persistentCache,
        private readonly CacheInterface            $cache
    ) {
    }

    /**
     * @param string $planId
     * @param CheckoutPageKeyEnum $page
     * @param CheckoutTimePeriodEnum $timePeriod
     * @param User $user
     * @param array|null $addOnIds
     * @return CheckoutPage
     * @throws GraphQLException
     * @throws InvalidArgumentException
     */
    public function getCheckoutPage(
        string                 $planId,
        CheckoutPageKeyEnum    $page,
        CheckoutTimePeriodEnum $timePeriod,
        User                   $user,
        ?array                 $addOnIds = null,
    ): CheckoutPage {
        if ($page === CheckoutPageKeyEnum::CONFIRMATION) {
            $checkoutSession = $this->persistentCache->get("checkout_session_{$user->getGuid()}");
            if (!$checkoutSession) {
                throw new GraphQLException('No completed checkout has been found', 400);
            }
            $checkoutSession = json_decode($checkoutSession, true);

            $planId = $checkoutSession['plan_id'];
            $timePeriod = CheckoutTimePeriodEnum::from($checkoutSession['time_period']);
            $addOnIds = $checkoutSession['add_on_ids'];
        }

        $planInfo = $this->getCheckoutProduct($planId);

        $planDetails = $this->strapiService->getPlan($planId);
        $addons = [];
        if (count($planInfo['addons'])) {
            $addons = $this->strapiService->getPlanAddons(array_keys($planInfo['addons']));
        }
        $page = $this->strapiService->getCheckoutPage($page);

        return $this->buildResponse($user, $planInfo, $planDetails, $addons, $page, $timePeriod, $addOnIds);
    }

    /**
     * @param string $planId
     * @return array
     * @throws ApiErrorException
     * @throws GraphQLException
     */
    private function getCheckoutProduct(string $planId): array
    {
        try {
            $product = $this->stripeProductService->getProductByKey($planId);
            $productPrices = $this->stripeProductPriceService->getPricesByProduct($product->id);

            $productAddons = $this->stripeProductService->getProductsByType(
                productType: ProductTypeEnum::tryFrom($product->metadata['type']),
                productSubType: ProductSubTypeEnum::ADDON
            );

            return [
                'price' => $this->getCheckoutProductPrices($productPrices, $planId),
                'addons' => $this->getCheckoutStripeAddons($productAddons),
            ];
        } catch (NotFoundException $e) {
            throw new GraphQLException('Invalid plan', 400);
        } catch (ServerErrorException $e) {
            throw new GraphQLException('An error occurred whilst fetching the product\'s price', 500);
        }
    }

    /**
     * @param SearchResult $productPrices
     * @param string $planId
     * @return array
     */
    private function getCheckoutProductPrices(SearchResult $productPrices, string $planId): array
    {
        $prices = [];

        /**
         * @var Price $price
         */
        foreach ($productPrices->getIterator() as $price) {
            switch ($price->lookup_key) {
                case "{$planId}:" . strtolower(CheckoutTimePeriodEnum::MONTHLY->name):
                    $prices[CheckoutTimePeriodEnum::MONTHLY->name] = $price->unit_amount;
                    break;
                case "{$planId}:" . strtolower(CheckoutTimePeriodEnum::YEARLY->name):
                    $prices[CheckoutTimePeriodEnum::YEARLY->name] = $price->unit_amount;
                    break;
            }
        }

        return $prices;
    }

    /**
     * @param SearchResult $productAddons
     * @return array
     * @throws ServerErrorException
     */
    private function getCheckoutStripeAddons(SearchResult $productAddons): array
    {
        $addons = [];

        /**
         * @var Product $addon
         */
        foreach ($productAddons->getIterator() as $addon) {
            $addonPrices = $this->stripeProductPriceService->getPricesByProduct($addon->id);

            $addons[$addon->metadata['key']] = [
                'price' => [
                    CheckoutTimePeriodEnum::MONTHLY->name => $this->getCheckoutStripeAddonPrices($addonPrices)
                ],
                'linked_product' => $addon->metadata['linked_product_key'] ?? null,
            ];

        }

        return $addons;
    }

    /**
     * @param SearchResult $addonPrices
     * @return array
     */
    private function getCheckoutStripeAddonPrices(SearchResult $addonPrices): array
    {
        $prices = [];

        /**
         * @var Price $price
         */
        foreach ($addonPrices->getIterator() as $price) {
            switch ($price->type) {
                case "recurring":
                    $prices["monthly_fee_cents"] = $price->unit_amount;
                    break;
                case "one_time":
                    $prices["one_time_fee_cents"] = $price->unit_amount;
                    break;
            }
        }

        return $prices;
    }

    /**
     * @param User $user
     * @param array $planInfo
     * @param Plan $plan
     * @param AddOn[] $addonsDetails
     * @param CheckoutPage $page
     * @param CheckoutTimePeriodEnum $timePeriod
     * @param array|null $addonIds
     * @return CheckoutPage
     * @throws InvalidArgumentException
     */
    private function buildResponse(
        User                   $user,
        array                  $planInfo,
        Plan                   $plan,
        iterable               $addonsDetails,
        CheckoutPage           $page,
        CheckoutTimePeriodEnum $timePeriod,
        ?array                 $addonIds
    ): CheckoutPage {
        $addons = [];
        $addonsSummary = [];

        $totalMonthlyCost = 0;
        $totalOneFeeCost = 0;
        /**
         * @var AddOn $addon
         */
        foreach ($addonsDetails as $addon) {
            $addon->monthlyFeeCents = $planInfo['addons'][$addon->id]['price'][CheckoutTimePeriodEnum::MONTHLY->name]['monthly_fee_cents'] ?? null;
            $addon->oneTimeFeeCents = $planInfo['addons'][$addon->id]['price'][CheckoutTimePeriodEnum::MONTHLY->name]['one_time_fee_cents'] ?? null;

            if ($linkedProduct = $planInfo['addons'][$addon->id]['linked_product']) {
                if ($cache = $this->cache->get("checkout_addons_{$user->getGuid()}")) {
                    $cache = json_decode($cache) ?? [];

                    if (
                        in_array($addon->id, $cache, true) // If the addon was in the basket
                    ) {
                        if (
                            !in_array($addon->id, $addonIds ?? [], true) && // And it's not in the basket now
                            ($index = array_search($linkedProduct, $addonIds ?? [], true)) !== false // Check if the linked product is in the basket
                        ) {
                            $addonIds = array_slice($addonIds, $index + 1, 1); // Remove the linked product from the basket
                        } elseif (
                            in_array($addon->id, $addonIds ?? [], true) && // If the addon is still in the basket
                            in_array($linkedProduct, $cache, true) && // And the linked product was in the basket before
                            !in_array($linkedProduct, $addonIds ?? [], true) // But the linked product isn't in the basket
                        ) {
                            $addonIds = array_slice($addonIds, array_search($addon->id, $addonIds, true) + 1, 1); // Remove the addon from the basket
                        }
                    } elseif (in_array($addon->id, $addonIds ?? [], true)) { // If the addon wasn't in the basket before but is now
                        if (
                            !in_array($linkedProduct, $addonIds ?? [], true) // If the linked product isn't in the basket
                        ) {
                            $addonIds[] = $linkedProduct; // Add the linked product to the basket
                        }
                    } elseif (in_array($linkedProduct, $addonIds ?? [], true)) { // If the linked product was in the basket before, isn't now and the linked product is in basket
                        $addonIds[] = $addon->id; // Add the addon to the basket
                    }
                } elseif (in_array($addon->id, $addonIds ?? [], true)) { // If the addon wasn't in the basket before but is now
                    if (
                        !in_array($linkedProduct, $addonIds ?? [], true) // If the linked product isn't in the basket
                    ) {
                        $addonIds[] = $linkedProduct; // Add the linked product to the basket
                    }
                } elseif (in_array($linkedProduct, $addonIds ?? [], true)) { // If the linked product was in the basket before, isn't now and the linked product is in basket
                    $addonIds[] = $addon->id; // Add the addon to the basket
                }
            }

            $addon->inBasket = false;
            if ($addonIds && in_array($addon->id, $addonIds, true)) {
                $totalMonthlyCost += $addon->monthlyFeeCents ?? 0;
                $totalOneFeeCost += $addon->oneTimeFeeCents ?? 0;
                $addon->inBasket = true;
                $addonsSummary[] = new AddOnSummary(
                    id: $addon->id,
                    name: $addon->name,
                    monthlyFeeCents: $addon->monthlyFeeCents,
                    oneTimeFeeCents: $addon->oneTimeFeeCents,
                );
            }

            $addons[] = $addon;
        }

        $this->cache->set("checkout_addons_{$user->getGuid()}", json_encode($addonIds), 60 * 60 * 24);

        $plan->monthlyFeeCents = $planInfo['price'][$timePeriod->name];
        $totalMonthlyCost += $plan->monthlyFeeCents;

        $page->plan = $plan;
        $page->summary = new Summary(
            planSummary: new PlanSummary(
                id: $plan->id,
                name: $plan->name,
                monthlyFeeCents: $plan->monthlyFeeCents,
            ),
            totalMonthlyFeeCents: $totalMonthlyCost,
            totalInitialFeeCents: $totalMonthlyCost + $totalOneFeeCost,
            addonsSummary: $addonsSummary,
        );
        $page->addOns = $addons;
        $page->totalAnnualSavingsCents = abs($planInfo['price'][CheckoutTimePeriodEnum::YEARLY->name] - $planInfo['price'][CheckoutTimePeriodEnum::MONTHLY->name]) * 12;
        $page->timePeriod = $timePeriod;

        return $page;
    }

    /**
     * @param string $planId
     * @return void
     * @throws GraphQLException
     */
    public function testStrapiIntegration(string $planId): void
    {
        $this->strapiService->getPlan($planId);
        foreach ($this->strapiService->getPlanAddons(['mobile_app']) as $addon) {
            echo $addon->name;
        }
    }
}
