<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Checkout\Controllers;

use Minds\Core\Payments\Checkout\Enums\CheckoutTimePeriodEnum;
use Minds\Core\Payments\Checkout\Services\CheckoutService;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Query;
use Zend\Diactoros\Response\RedirectResponse;

class CheckoutController
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
    ) {
    }

    /**
     * @param string $planId
     * @param string[]|null $addOnIds
     * @param CheckoutTimePeriodEnum $timePeriod
     * @return string
     */
    #[Query]
    #[Logged]
    public function getCheckoutLink(
        string $planId,
        CheckoutTimePeriodEnum $timePeriod,
        #[InjectUser] User $user,
        ?array $addOnIds = null,
    ): string {
        return $this->checkoutService->generateCheckoutLink(
            user: $user,
            planId: $planId,
            timePeriod: $timePeriod,
            addOnIds: $addOnIds
        );
    }

    public function completeCheckout(): RedirectResponse
    {
        return new RedirectResponse('http://localhost:4200/networks/checkout?confirmed=true');
    }
}
