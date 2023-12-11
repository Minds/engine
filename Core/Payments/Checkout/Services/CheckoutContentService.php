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
        private readonly CacheInterface            $cache
    ) {
    }

    /**
     * @param string $planId
     * @param CheckoutPageKeyEnum $page
     * @param CheckoutTimePeriodEnum $timePeriod
     * @param array|null $addOnIds
     * @return CheckoutPage
     * @throws GraphQLException
     */
    public function getCheckoutPage(
        string $planId,
        CheckoutPageKeyEnum $page,
        CheckoutTimePeriodEnum $timePeriod,
        User $user,
        ?array $addOnIds = null,
    ): CheckoutPage {
        if ($page === CheckoutPageKeyEnum::CONFIRMATION) {
            $checkoutSession = $this->cache->get("checkout_session_{$user->getGuid()}");
            if (!$checkoutSession) {
                throw new GraphQLException('No completed checkout has been found', 400);
            }
            $checkoutSession = json_decode($checkoutSession, true);

            $planId = $checkoutSession['plan_id'];
            $timePeriod = CheckoutTimePeriodEnum::from($checkoutSession['time_period']);
            $addOnIds = $checkoutSession['add_on_ids'];
        }

        $planInfo = $this->getCheckoutProduct($user, $planId);

        $planDetails = $this->strapiService->getPlan($planId);
        $addons = [];
        if (count($planInfo['addons'])) {
            $addons = $this->strapiService->getPlanAddons(array_keys($planInfo['addons']));
        }
        $page = $this->strapiService->getTenantCheckoutPage($page);

        return $this->buildResponse($planInfo, $planDetails, $addons, $page, $timePeriod, $addOnIds);
    }

    /**
     * @param User $user
     * @param string $planId
     * @return Product
     * @throws GraphQLException
     */
    private function getCheckoutProduct(User $user, string $planId): array
    {
        try {
            $product = $this->stripeProductService->getProductByKey($user, $planId);
            $productPrices = $this->stripeProductPriceService->getPricesByProduct($user, $product->id);

            $productAddons = $this->stripeProductService->getProductsByType(
                user: $user,
                productType: ProductTypeEnum::NETWORK,
                productSubType: ProductSubTypeEnum::ADDON
            );

            return [
                'price' => (function (SearchResult $productPrices, string $planId): array {
                    $prices = [];

                    /**
                     * @var Price $price
                     */
                    foreach ($productPrices->data as $price) {
                        switch ($price->lookup_key) {
                            case "{$planId}_" . strtolower(CheckoutTimePeriodEnum::MONTHLY->name):
                                $prices[CheckoutTimePeriodEnum::MONTHLY->name] = $price->unit_amount;
                                break;
                            case "{$planId}_" . strtolower(CheckoutTimePeriodEnum::YEARLY->name):
                                $prices[CheckoutTimePeriodEnum::YEARLY->name] = $price->unit_amount;
                                break;
                        }
                    }

                    return $prices;
                })($productPrices, $planId),
                'addons' => (function (SearchResult $productAddons, User $user): array {
                    $addons = [];

                    /**
                     * @var Product $addon
                     */
                    foreach ($productAddons->data as $addon) {
                        $addonPrices = $this->stripeProductPriceService->getPricesByProduct($user, $addon->id);

                        $addons[$addon->metadata['key']] = [
                            CheckoutTimePeriodEnum::MONTHLY->name => (function (SearchResult $addonPrices): array {
                                $prices = [];

                                /**
                                 * @var Price $price
                                 */
                                foreach ($addonPrices->data as $price) {
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
                            })($addonPrices)
                        ];
                    }

                    return $addons;
                })($productAddons, $user),
            ];
        } catch (NotFoundException $e) {
            throw new GraphQLException('Invalid plan', 400);
        } catch (ServerErrorException $e) {
            throw new GraphQLException('An error occurred whilst fetching the product\'s price', 500);
        }
    }

    /**
     * @param array $planInfo
     * @param Plan $plan
     * @param AddOn[] $addonsDetails
     * @param CheckoutPage $page
     * @param CheckoutTimePeriodEnum $timePeriod
     * @param array|null $addonIds
     * @return CheckoutPage
     */
    private function buildResponse(
        array $planInfo,
        Plan $plan,
        iterable $addonsDetails,
        CheckoutPage $page,
        CheckoutTimePeriodEnum $timePeriod,
        ?array $addonIds
    ): CheckoutPage {
        $addons = [];
        $addonsSummary = [];

        $totalMonthlyCost = 0;
        $totalOneFeeCost = 0;
        /**
         * @var AddOn $addon
         */
        foreach ($addonsDetails as $addon) {
            $addon->monthlyFeeCents = $planInfo['addons'][$addon->id][CheckoutTimePeriodEnum::MONTHLY->name]['monthly_fee_cents'];
            $addon->oneTimeFeeCents = $planInfo['addons'][$addon->id][CheckoutTimePeriodEnum::MONTHLY->name]['one_time_fee_cents'] ?? null;

            if ($addonIds && in_array($addon->id, $addonIds, true)) {
                $totalMonthlyCost += $addon->monthlyFeeCents;
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
        $this->strapiService->getTenantPlan($planId);
        foreach ($this->strapiService->getTenantPlanAddons(['mobile_app']) as $addon) {
            echo $addon->name;
        }
    }

    /**
     * @param User $user
     * @param string $priceId
     * @return Price
     * @throws GraphQLException
     */
    private function getCheckoutProductPrice(User $user, string $priceId): Price
    {
        try {
            return $this->stripeProductPriceService->getPriceDetailsById($user, $priceId);
        } catch (ServerErrorException $e) {
            throw new GraphQLException('An error occurred while fetching the product price', 500);
        }
    }
}
