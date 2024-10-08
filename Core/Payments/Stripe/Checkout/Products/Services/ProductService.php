<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Checkout\Products\Services;

use InvalidArgumentException;
use Minds\Core\Config\Config;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductPriceBillingPeriodEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductPriceCurrencyEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductPricingModelEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductSubTypeEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductTypeEnum;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Exceptions\NotFoundException;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException as CacheInvalidArgumentException;
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
     * @param ProductTypeEnum $productType
     * @param ProductSubTypeEnum|null $productSubType
     * @return SearchResult<Product>
     * @throws CacheInvalidArgumentException
     * @throws NotFoundException
     */
    public function getProductsByType(
        ProductTypeEnum     $productType,
        ?ProductSubTypeEnum $productSubType = null
    ): SearchResult {
        $cacheKey = $this->buildCacheKey("products_{$productType->value}_{$productSubType?->value}");
        if ($products = $this->cache->get($cacheKey)) {
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

        $this->cache->set($cacheKey, serialize($products), self::CACHE_TTL);

        return $products;
    }

    /**
     * @param string $productId The Stripe product ID
     * @return Product
     * @throws ApiErrorException
     * @throws NotFoundException
     */
    public function getProductById(string $productId): Product
    {
        $product = $this->stripeClient
            ->products
            ->retrieve($productId);

        return $product;
    }

    /**
     * @param string $productKey
     * @return Product
     * @throws ApiErrorException
     * @throws CacheInvalidArgumentException
     * @throws NotFoundException
     */
    public function getProductByKey(string $productKey): Product
    {
        $cacheKey = $this->buildCacheKey("product_$productKey");
        if ($product = $this->cache->get($cacheKey)) {
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

        $this->cache->set($cacheKey, serialize($product), self::CACHE_TTL);

        return $product;
    }

    /**
     * @param array $metadata
     * @param ProductTypeEnum|null $productType
     * @param array $availableProducts
     * @return Product[]
     * @throws NotFoundException
     */
    public function getProductsByMetadata(
        array            $metadata,
        ?ProductTypeEnum $productType = null,
        array            $availableProducts = []
    ): iterable {
        if (count($metadata) > 10) {
            throw new InvalidArgumentException("You can only search for up to 10 metadata keys at a time.");
        }

        $query = "";
        foreach ($metadata as $key => $value) {
            $query .= "metadata['$key']:'$value' AND ";
        }

        $query = rtrim($query, " AND");

        $query .= " AND active:'true'";

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
            yield $product;
        }
    }

    /**
     * @param int $internalProductId
     * @param string $name
     * @param int $priceInCents
     * @param ProductPriceBillingPeriodEnum $billingPeriod
     * @param ProductPricingModelEnum $pricingModel
     * @param ProductTypeEnum $productType
     * @param ProductPriceCurrencyEnum $currency
     * @param string|null $description
     * @return Product
     */
    public function createProduct(
        int                           $internalProductId, // How we identify the product internally
        string                        $name,
        int                           $priceInCents,
        ProductPriceBillingPeriodEnum $billingPeriod,
        ProductPricingModelEnum       $pricingModel,
        ProductTypeEnum               $productType,
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
                'type' => $productType->value,
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

        return $this->stripeClient
            ->products
            ->create($productDetails);
    }

    /**
     * @param string $productId
     * @param string $name
     * @param string|null $description
     * @return Product
     */
    public function updateProduct(
        string  $productId,
        string  $name,
        ?string $description = null,
    ): Product {
        $productDetails = [
            'name' => $name,
        ];

        if ($description !== null) {
            $productDetails['description'] = $description;
        }

        return $this->stripeClient
            ->products
            ->update($productId, $productDetails);
    }

    /**
     * @param string $productId
     * @return bool
     */
    public function archiveProduct(
        string $productId
    ): bool {
        $this->stripeClient
            ->products
            ->update($productId, [
                'active' => false,
            ]);

        return true;
    }

    /**
     * @param string $productId
     * @return bool
     */
    public function deleteProduct(
        string $productId
    ): bool {
        $this->stripeClient
            ->products
            ->delete($productId);

        return true;
    }

    private function buildCacheKey(string $key): string
    {
        return $this->stripeClient->getApiKeyHash() . '::' . $key;
    }
}
