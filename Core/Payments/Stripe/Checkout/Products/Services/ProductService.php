<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Checkout\Products\Services;

use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductSubTypeEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductTypeEnum;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Psr\SimpleCache\CacheInterface;
use Stripe\Exception\ApiErrorException;
use Stripe\Product;
use Stripe\SearchResult;

class ProductService
{
    private const CACHE_TTL = 60 * 5; // 5 minutes

    public function __construct(
        private readonly StripeClient $stripeClient,
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * @param User $user
     * @param ProductTypeEnum $productType
     * @param ProductSubTypeEnum|null $productSubType
     * @return SearchResult<Product>
     * @throws NotFoundException
     * @throws ApiErrorException
     */
    public function getProductsByType(
        ProductTypeEnum $productType,
        ?ProductSubTypeEnum $productSubType = null
    ): SearchResult {
        if ($products = $this->cache->get("products_{$productType->value}_{$productSubType?->value}")) {
            return unserialize($products);
        }
        $query = "metadata['type']:'$productType->value'";

        if ($productSubType) {
            $query .= " AND metadata['sub_type']:'$productSubType->value'";
        }

        $products = $this->stripeClient
            ->products
            ->search([
                'query' => $query,
            ]);

        if ($products->count() === 0) {
            throw new NotFoundException("No products were found.");
        }

        $this->cache->set("products_{$productType->value}_{$productSubType?->value}", serialize($products), self::CACHE_TTL);

        return $products;
    }

    /**
     * @param User $user
     * @param string $productKey
     * @return Product
     * @throws NotFoundException
     * @throws ApiErrorException
     */
    public function getProductByKey(string $productKey): Product
    {
        if ($product = $this->cache->get("product_$productKey")) {
            return unserialize($product);
        }

        /**
         * @var SearchResult<Product> $results
         */
        $results = $this->stripeClient
            ->products
            ->search([
                'query' => "metadata['key']:'$productKey'"
            ]);

        if ($results->count() === 0) {
            throw new NotFoundException("The requested product could not be found.");
        }

        $product = $results->first();

        $this->cache->set("product_$productKey", serialize($product), self::CACHE_TTL);

        return $product;
    }
}
