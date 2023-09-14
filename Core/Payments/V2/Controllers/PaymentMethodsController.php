<?php
declare(strict_types=1);

namespace Minds\Core\Payments\V2\Controllers;

use Exception;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\V2\Models\PaymentMethod;
use Minds\Core\Payments\V2\PaymentMethods\Manager as PaymentMethodsManager;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Query;

class PaymentMethodsController
{
    public function __construct(
        private readonly PaymentMethodsManager $paymentMethodsManager,
        private readonly Logger $logger
    ) {
    }

    /**
     * Get a list of payment methods for the logged in user
     * @param User $loggedInUser
     * @return PaymentMethod[]
     * @throws Exception
     */
    #[Query]
    #[Logged]
    public function paymentMethods(
        #[InjectUser] User $loggedInUser,
        ?GiftCardProductIdEnum $productId = null
    ): array {
        return $this->paymentMethodsManager->getPaymentMethods($loggedInUser, $productId);
    }
}
