<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Checkout\Products\Services;

use Exception;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use Psr\SimpleCache\CacheInterface;
use Stripe\Price;
use Stripe\SearchResult;

class ProductPriceService
{
    private const CACHE_TTL = 60 * 5; // 5 minutes
    public function __construct(
        private readonly StripeClient $stripeClient,
        private readonly CacheInterface $cache
    ) {
    }

    public function getPriceDetailsByLookupKey(
        string $lookUpKey
    ): ?Price {
        if ($price = $this->cache->get("product_price_$lookUpKey")) {
            return unserialize($price);
        }

        try {
            $results = $this->stripeClient
                ->prices
                ->search(
                    params: [
                        'query' => "lookup_key:'$lookUpKey'",
                    ]
                );

            if (!$results->count()) {
                return null;
            }

            $price = $results->first();

            $this->cache->set("product_price_$lookUpKey", serialize($price), self::CACHE_TTL);

            return $price;
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
    public function getPriceDetailsById(string $priceId): ?Price
    {
        if ($price = $this->cache->get("product_price_$priceId")) {
            return unserialize($price);
        }

        try {
            $price = $this->stripeClient
                ->prices
                ->retrieve($priceId);

            $this->cache->set("product_price_$priceId", serialize($price), self::CACHE_TTL);

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
    public function getPricesByProduct(string $productId): SearchResult
    {
        if ($prices = $this->cache->get("product_prices_$productId")) {
            return unserialize($prices);
        }
        try {
            $prices = $this->stripeClient
                ->prices
                ->search(
                    params: [
                        'query' => "product:'$productId' AND active:'true'"
                    ]
                );

            if ($prices->count() === 0) {
                throw new NotFoundException('No prices found for product');
            }

            $this->cache->set("product_prices_$productId", serialize($prices), self::CACHE_TTL);

            return $prices;
        } catch (Exception $e) {
            throw new ServerErrorException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
