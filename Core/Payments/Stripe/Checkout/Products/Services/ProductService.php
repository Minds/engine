<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Checkout\Products\Services;

use Minds\Core\Config\Config;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductPriceBillingPeriodEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductPriceCurrencyEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductPricingModelEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductSubTypeEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductTypeEnum;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Stripe\Exception\ApiErrorException;
use Stripe\Product;
use Stripe\SearchResult;

class ProductService
{
    private const CACHE_TTL = 60 * 5; // 5 minutes

    public function __construct(
        private readonly StripeClient   $stripeClient,
        private readonly CacheInterface $cache,
        private readonly Config         $config
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
        ProductTypeEnum     $productType,
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
     * @param string $productId The Stripe product ID
     * @return Product
     * @throws ApiErrorException
     * @throws NotFoundException
     * @throws InvalidArgumentException
     */
    public function getProductById(string $productId): Product
    {
        if ($productKey = $this->cache->get("product_$productId")) {
            return $this->getProductByKey($productKey);
        }

        $product = $this->stripeClient
            ->products
            ->retrieve($productId);

        $productKey = $product->metadata['key'];

        $this->cache->set("product_$productKey", serialize($product), self::CACHE_TTL);
        $this->cache->set("product_$product->id", "product_$productKey", self::CACHE_TTL);

        return $product;
    }

    /**
     * @param string $productKey
     * @return Product
     * @throws ApiErrorException
     * @throws InvalidArgumentException
     * @throws NotFoundException
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
        $this->cache->set("product_$product->id", "product_$productKey", self::CACHE_TTL);

        return $product;
    }

    /**
     * @param array $productKeys
     * @return Product[]
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function getProductsByKeys(
        array $productKeys
    ): iterable {
        $productKeysToFetch = [];
        foreach ($productKeys as $productKey) {
            if ($product = $this->cache->get("product_$productKey")) {
                yield unserialize($product);
                continue;
            }
            $productKeysToFetch[] = $productKey;
        }

        if (count($productKeysToFetch) === 0) {
            return;
        }

        $query = "";
        foreach ($productKeysToFetch as $productKey) {
            $query .= "metadata['key']:'$productKey' OR ";
        }

        $query = rtrim($query, " OR");

        /**
         * @var SearchResult<Product> $products
         */
        $products = $this->stripeClient
            ->products
            ->search([
                'query' => $query,
            ]);

        if ($products->count() === 0) {
            throw new NotFoundException("No products were found.");
        }

        foreach ($products as $product) {
            $productKey = $product->metadata['key'];

            $this->cache->set("product_$productKey", serialize($product), self::CACHE_TTL);
            $this->cache->set("product_$product->id", "product_$productKey", self::CACHE_TTL);

            yield $product;
        }
    }

    /**
     * @param int $internalProductId
     * @param string $name
     * @param int $priceInCents
     * @param ProductPriceBillingPeriodEnum $billingPeriod
     * @param ProductPricingModelEnum $pricingModel
     * @param ProductPriceCurrencyEnum $currency
     * @param string|null $description
     * @return Product
     * @throws ApiErrorException
     * @throws InvalidArgumentException
     */
    public function createProduct(
        int                           $internalProductId, // How we identify the product internally
        string                        $name,
        int                           $priceInCents,
        ProductPriceBillingPeriodEnum $billingPeriod,
        ProductPricingModelEnum       $pricingModel,
        ProductPriceCurrencyEnum      $currency = ProductPriceCurrencyEnum::USD,
        ?string                       $description = null,
    ): Product {
        $productKey = "tenant:{$this->config->get('tenant_id')}:$internalProductId";

        $productDetails = [
            'name' => $name,
            'default_price_data' => [
                'currency' => $currency->value,
                'unit_amount' => $priceInCents,
            ],
            'metadata' => [
                'key' => $productKey,
                'type' => ProductTypeEnum::SITE_MEMBERSHIP->value,
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'billing_period' => $billingPeriod->value,
            ],
        ];

        if ($description) {
            $productDetails['description'] = $description;
        }

        if ($pricingModel === ProductPricingModelEnum::RECURRING) {
            $productDetails['default_price_data']['recurring'] = [
                'interval' => $billingPeriod->value,
            ];
        }

        $product = $this->stripeClient
            ->products
            ->create($productDetails);

        $this->cache->set("product_$productKey", serialize($product), self::CACHE_TTL);
        $this->cache->set("product_$product->id", "product_$productKey", self::CACHE_TTL);

        return $product;
    }
}
