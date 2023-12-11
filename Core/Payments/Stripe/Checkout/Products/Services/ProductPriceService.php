<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Checkout\Products\Services;

use Exception;
use Minds\Core\Payments\Stripe\Instances\ProductPriceInstance;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use Stripe\Price;
use Stripe\SearchResult;

class ProductPriceService
{
    public function __construct(
        private readonly ProductPriceInstance $priceInstance
    ) {
    }

    public function getPriceDetailsByLookupKey(
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

    /**
     * @param User $user
     * @param string $priceId
     * @return Price|null
     * @throws ServerErrorException
     */
    public function getPriceDetailsById(User $user, string $priceId): ?Price
    {
        try {
            $price = $this->priceInstance
                ->withUser($user)
                ->retrieve($priceId);
            return $price;
        } catch (Exception $e) {
            throw new ServerErrorException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param User $user
     * @param string $productId
     * @return SearchResult
     * @throws ServerErrorException
     */
    public function getPricesByProduct(User $user, string $productId): SearchResult
    {
        try {
            $prices = $this->priceInstance
                ->withUser($user)
                ->search([
                    'query' => "product:'$productId' AND active:'true'"
                ]);

            if ($prices->count() === 0) {
                throw new NotFoundException('No prices found for product');
            }

            return $prices;
        } catch (Exception $e) {
            throw new ServerErrorException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
