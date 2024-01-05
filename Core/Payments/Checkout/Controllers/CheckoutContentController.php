<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Checkout\Controllers;

use Minds\Core\Payments\Checkout\Enums\CheckoutPageKeyEnum;
use Minds\Core\Payments\Checkout\Enums\CheckoutTimePeriodEnum;
use Minds\Core\Payments\Checkout\Services\CheckoutContentService;
use Minds\Core\Payments\Checkout\Types\CheckoutPage;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class CheckoutContentController
{
    public function __construct(
        private readonly CheckoutContentService $checkoutContentService
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
        #[InjectUser] User $user,
        ?array $addOnIds = null,
    ): CheckoutPage {
        return $this->checkoutContentService->getCheckoutPage(
            planId: $planId,
            page: $page,
            timePeriod: $timePeriod,
            user: $user,
            addOnIds: $addOnIds
        );
    }
}
