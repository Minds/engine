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

        $cacheKey = $this->buildCacheKey("product_price_$lookUpKey");

        if ($price = $this->cache->get($cacheKey)) {
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

            $this->cache->set($cacheKey, serialize($price), self::CACHE_TTL);

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
        $cacheKey = "product_price_$priceId";

        if ($price = $this->cache->get($cacheKey)) {
            return unserialize($price);
        }

        try {
            $price = $this->stripeClient
                ->prices
                ->retrieve($priceId);

            $this->cache->set($cacheKey, serialize($price), self::CACHE_TTL);

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
        $cacheKey = "product_prices_$productId";

        if ($prices = $this->cache->get($cacheKey)) {
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

            $this->cache->set($cacheKey, serialize($prices), self::CACHE_TTL);

            return $prices;
        } catch (Exception $e) {
            throw new ServerErrorException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function buildCacheKey(string $key): string
    {
        return $this->stripeClient->getApiKeyHash() . '::' . $key;
    }
}
