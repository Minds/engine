<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Controllers;

use Minds\Core\MultiTenant\Enums\CheckoutPageKeyEnum;
use Minds\Core\MultiTenant\Enums\CheckoutTimePeriodEnum;
use Minds\Core\MultiTenant\Services\CheckoutService;
use Minds\Core\MultiTenant\Types\Checkout\CheckoutPage;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class CheckoutController
{
    public function __construct(
        private readonly CheckoutService $checkoutService
    ) {
    }

    /**
     * @param string $planId
     * @param CheckoutPageKeyEnum $page
     * @param CheckoutTimePeriodEnum $timePeriod
     * @param string[]|null $addOnIds
     * @return CheckoutPage
     * @throws GraphQLException
     */
    #[Query]
    #[Logged]
    public function getCheckoutPage(
        string $planId,
        CheckoutPageKeyEnum $page,
        CheckoutTimePeriodEnum $timePeriod,
        array|null $addOnIds = null,
    ): CheckoutPage {
        return $this->checkoutService->getCheckoutPage($planId, $page, $timePeriod, $addOnIds);
    }
}
