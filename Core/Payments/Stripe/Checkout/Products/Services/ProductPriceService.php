<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Checkout\Products\Services;

use Exception;
use Minds\Core\Payments\Stripe\Instances\ProductPriceInstance;
use Minds\Entities\User;
use Stripe\Price;

class ProductPriceService
{
    public function __construct(
        private readonly ProductPriceInstance $priceInstance
    ) {
    }

    public function getPriceDetails(
        User $user,
        string $lookUpKey
    ): ?Price {
        try {
            $results = $this->priceInstance
                ->withUser($user)
                ->search([
                    'query' => "lookup_key:'$lookUpKey'",
                ]);

            if (!$results->count()) {
                return null;
            }

            return $results->first();
        } catch (Exception $e) {
            return null;
        }
    }
}
