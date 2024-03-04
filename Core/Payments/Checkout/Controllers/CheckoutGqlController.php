<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Checkout\Controllers;

use Minds\Core\Payments\Checkout\Enums\CheckoutTimePeriodEnum;
use Minds\Core\Payments\Checkout\Services\CheckoutService;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Psr\SimpleCache\InvalidArgumentException;
use Stripe\Exception\ApiErrorException;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class CheckoutGqlController
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
    ) {
    }

    /**
     * @param string $planId
     * @param CheckoutTimePeriodEnum $timePeriod
     * @param string[]|null $addOnIds
     * @param bool $isTrialUpgrade
     * @return string
     * @throws ServerErrorException
     * @throws InvalidArgumentException
     * @throws ApiErrorException
     * @throws GraphQLException
     */
    #[Query]
    #[Logged]
    public function getCheckoutLink(
        string                 $planId,
        CheckoutTimePeriodEnum $timePeriod,
        #[InjectUser] User     $user,
        ?array                 $addOnIds = null,
        bool                   $isTrialUpgrade = false
    ): string {
        return $this->checkoutService->generateCheckoutLink(
            user: $user,
            planId: $planId,
            timePeriod: $timePeriod,
            addOnIds: $addOnIds,
            isTrialUpgrade: $isTrialUpgrade
        );
    }
}
