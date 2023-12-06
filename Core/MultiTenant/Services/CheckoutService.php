<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Services;

use Minds\Core\MultiTenant\Enums\CheckoutPageKeyEnum;
use Minds\Core\MultiTenant\Enums\CheckoutTimePeriodEnum;
use Minds\Core\MultiTenant\Types\Checkout\AddOn;
use Minds\Core\MultiTenant\Types\Checkout\AddOnSummary;
use Minds\Core\MultiTenant\Types\Checkout\CheckoutPage;
use Minds\Core\MultiTenant\Types\Checkout\Plan;
use Minds\Core\MultiTenant\Types\Checkout\PlanSummary;
use Minds\Core\MultiTenant\Types\Checkout\Summary;
use Minds\Core\Strapi\Services\StrapiService;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class CheckoutService
{
    public function __construct(
        private readonly StrapiService $strapiService,
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
        ?array $addOnIds = null,
    ): CheckoutPage {
        // TODO: to be replaced with integration with Lago
        if (!in_array($planId, array_keys($this->getPlans()), true)) {
            throw new GraphQLException('Invalid plan', 400);
        }

        $planInfo = $this->getPlans()[$planId];

        $plan = $this->strapiService->getTenantPlan($planId);
        $addons = $this->strapiService->getTenantPlanAddons(array_keys($planInfo['addons']));
        $page = $this->strapiService->getTenantCheckoutPage($page);

        return $this->getMock($planInfo, $plan, $addons, $page, $timePeriod, $addOnIds);
    }

    private function getPlans(): array
    {
        return [
            'networks_team' => [
                'addons' => [
                    'mobile_app' => [
                        CheckoutTimePeriodEnum::MONTHLY->name => [
                            'monthly_fee_cents' => 100000,
                            'one_time_fee_cents' => 500000,
                        ],
                    ],
                    'technical_support' => [
                        CheckoutTimePeriodEnum::MONTHLY->name => [
                            'monthly_fee_cents' => 100000,
                        ],
                    ],
                ],
                'price' => [
                    CheckoutTimePeriodEnum::MONTHLY->name => 6000,
                    CheckoutTimePeriodEnum::YEARLY->name => 5000,
                ],
            ],
            'networks_community' => [
                'addons' => [
                    'mobile_app' => [
                        CheckoutTimePeriodEnum::MONTHLY->name => [
                            'monthly_fee_cents' => 100000,
                            'one_time_fee_cents' => 500000,
                        ],
                    ],
                    'technical_support' => [
                        CheckoutTimePeriodEnum::MONTHLY->name => [
                            'monthly_fee_cents' => 100000,
                        ],
                    ],
                    'moderation' => [
                        CheckoutTimePeriodEnum::MONTHLY->name => [
                            'monthly_fee_cents' => 10000,
                        ],
                    ],
                ],
                'price' => [
                    CheckoutTimePeriodEnum::MONTHLY->name => 60000,
                    CheckoutTimePeriodEnum::YEARLY->name => 50000,
                ],
            ],
            'networks_enterprise' => [
                'addons' => [
                    'mobile_app' => [
                        CheckoutTimePeriodEnum::MONTHLY->name => [
                            'monthly_fee_cents' => 100000,
                            'one_time_fee_cents' => 500000,
                        ],
                    ],
                    'technical_support' => [
                        CheckoutTimePeriodEnum::MONTHLY->name => [
                            'monthly_fee_cents' => 100000,
                        ],
                    ],
                    'moderation' => [
                        CheckoutTimePeriodEnum::MONTHLY->name => [
                            'monthly_fee_cents' => 10000,
                        ],
                    ],
                ],
                'price' => [
                    CheckoutTimePeriodEnum::MONTHLY->name => 120000,
                    CheckoutTimePeriodEnum::YEARLY->name => 100000,
                ],
            ],
        ];
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
    private function getMock(
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
     * @param array|null $addonIds
     * @return array
     */
    private function summaryAddons(?array $addonIds): array
    {
        $addonsSummary = [
            'mobile_app' => new AddOnSummary(
                id: 'mobile_app',
                name: 'Mobile app',
                monthlyFeeCents: 100000,
                oneTimeFeeCents: 500000,
            ),
            'technical_support' => new AddOnSummary(
                id: 'technical_support',
                name: 'Technical Support',
                monthlyFeeCents: 100000,
            ),
            'moderation' => new AddOnSummary(
                id: 'moderation',
                name: 'Moderation',
                monthlyFeeCents: 10000,
            ),
        ];

        return array_map(fn(string $addonId) => $addonsSummary[$addonId], $addonIds ?? []);
    }

    public function testStrapiIntegration(string $planId): void
    {
        $this->strapiService->getTenantPlan($planId);
        foreach ($this->strapiService->getTenantPlanAddons(['mobile_app']) as $addon) {
            echo $addon->name;
        }
    }
}
