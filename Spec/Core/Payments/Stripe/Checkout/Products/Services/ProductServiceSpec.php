<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\Stripe\Checkout\Products\Services;

use InvalidArgumentException;
use Minds\Core\Config\Config;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductPriceBillingPeriodEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductPriceCurrencyEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductPricingModelEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductSubTypeEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductTypeEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Exceptions\NotFoundException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Psr\SimpleCache\CacheInterface;
use ReflectionClass;
use Spec\Minds\Common\Traits\CommonMatchers;
use Stripe\Product as StripeProduct;
use Stripe\SearchResult as StripeSearchResult;
use Stripe\Service\ProductService as StripeProductService;

class ProductServiceSpec extends ObjectBehavior
{
    use CommonMatchers;

    private Collaborator $stripeProductServiceMock;
    private Collaborator $cacheMock;
    private Collaborator $configMock;

    private ReflectionClass $stripeClientMockFactory;
    private ReflectionClass $stripeProductMockFactory;

    private ReflectionClass $stripeSearchResultMockFactory;

    public function let(
        StripeProductService $stripeProductService,
        CacheInterface       $cache,
        Config               $config
    ): void {
        $this->stripeProductServiceMock = $stripeProductService;
        $this->cacheMock = $cache;
        $this->configMock = $config;

        $this->stripeClientMockFactory = new ReflectionClass(StripeClient::class);
        $this->stripeProductMockFactory = new ReflectionClass(StripeProduct::class);
        $this->stripeSearchResultMockFactory = new ReflectionClass(StripeSearchResult::class);

        $this->beConstructedWith(
            $this->prepareStripeClientMock(),
            $this->cacheMock,
            $this->configMock
        );
    }

    private function prepareStripeClientMock(): StripeClient
    {
        $stripeClientMock = $this->stripeClientMockFactory->newInstanceWithoutConstructor();
        $this->stripeClientMockFactory->getProperty('coreServiceFactory')
            ->setValue($stripeClientMock, new class($this->stripeProductServiceMock->getWrappedObject()) {
                public function __construct(
                    public StripeProductService $products
                ) {
                }

                public function __get(string $name)
                {
                    return $this->products;
                }
            });
        return $stripeClientMock;
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(ProductService::class);
    }

    public function it_should_get_products_by_type_NO_subtype(): void
    {
        $productType = ProductTypeEnum::NETWORK;
        $productSubType = null;

        $this->cacheMock->get("products_{$productType->value}_{$productSubType?->value}")
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $searchResultMock = $this->generateStripeSearchResultMock([
            $this->generateStripeProductMock(),
            $this->generateStripeProductMock(),
            $this->generateStripeProductMock()
        ]);

        $this->stripeProductServiceMock->search([
            'query' => "metadata['type']:'{$productType->value}'"
        ])
            ->shouldBeCalledOnce()
            ->willReturn($searchResultMock);

        $this->cacheMock->set("products_{$productType->value}_{$productSubType?->value}", serialize($searchResultMock), 60 * 5)
            ->shouldBeCalledOnce();

        $this->getProductsByType($productType, $productSubType);
    }

    private function generateStripeSearchResultMock(array $products): StripeSearchResult
    {
        $stripeSearchResultMock = $this->stripeSearchResultMockFactory->newInstanceWithoutConstructor();
        $this->stripeSearchResultMockFactory->getProperty('_values')
            ->setValue($stripeSearchResultMock, [
                'data' => $products
            ]);

        return $stripeSearchResultMock;
    }

    private function generateStripeProductMock(): StripeProduct
    {
        return $this->stripeProductMockFactory->newInstanceWithoutConstructor();
    }

    public function it_should_get_products_by_type_WITH_subtype(): void
    {
        $productType = ProductTypeEnum::NETWORK;
        $productSubType = ProductSubTypeEnum::ADDON;

        $this->cacheMock->get("products_{$productType->value}_{$productSubType?->value}")
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $searchResultMock = $this->generateStripeSearchResultMock([
            $this->generateStripeProductMock(),
            $this->generateStripeProductMock(),
            $this->generateStripeProductMock()
        ]);

        $this->stripeProductServiceMock->search([
            'query' => "metadata['type']:'{$productType->value}' AND metadata['sub_type']:'{$productSubType->value}'"
        ])
            ->shouldBeCalledOnce()
            ->willReturn($searchResultMock);

        $this->cacheMock->set("products_{$productType->value}_{$productSubType?->value}", serialize($searchResultMock), 60 * 5)
            ->shouldBeCalledOnce();

        $this->getProductsByType($productType, $productSubType);
    }

    public function it_should_get_products_by_type_WITH_cache(): void
    {
        $productType = ProductTypeEnum::NETWORK;
        $productSubType = ProductSubTypeEnum::ADDON;

        $searchResultMock = $this->generateStripeSearchResultMock([
            $this->generateStripeProductMock(),
            $this->generateStripeProductMock(),
            $this->generateStripeProductMock()
        ]);

        $this->cacheMock->get("products_{$productType->value}_{$productSubType?->value}")
            ->shouldBeCalledOnce()
            ->willReturn(serialize($searchResultMock));

        $this->getProductsByType($productType, $productSubType)
            ->shouldBeAnInstanceOf(StripeSearchResult::class);
    }

    public function it_should_get_product_by_id(): void
    {
        $this->stripeProductServiceMock->retrieve('product_id')
            ->shouldBeCalledOnce()
            ->willReturn($this->generateStripeProductMock());

        $this->getProductById('product_id')
            ->shouldBeAnInstanceOf(StripeProduct::class);
    }

    public function it_should_get_product_by_key(): void
    {
        $this->cacheMock->get('product_product_key')
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $searchResultMock = $this->generateStripeSearchResultMock([
            $this->generateStripeProductMock()
        ]);

        $this->stripeProductServiceMock->search([
            'query' => "metadata['key']:'product_key'"
        ])
            ->shouldBeCalledOnce()
            ->willReturn($searchResultMock);

        $this->cacheMock->set('product_product_key', serialize($searchResultMock->first()), 60 * 5)
            ->shouldBeCalledOnce();

        $this->getProductByKey('product_key')
            ->shouldBeAnInstanceOf(StripeProduct::class);
    }

    public function it_should_get_product_by_key_WITH_cache(): void
    {
        $this->cacheMock->get('product_product_key')
            ->shouldBeCalledOnce()
            ->willReturn(serialize($this->generateStripeProductMock()));

        $this->getProductByKey('product_key')
            ->shouldBeAnInstanceOf(StripeProduct::class);
    }

    public function it_should_try_to_get_product_by_key_and_THROW_not_found_exception(): void
    {
        $this->cacheMock->get('product_product_key')
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->stripeProductServiceMock->search([
            'query' => "metadata['key']:'product_key'"
        ])
            ->shouldBeCalledOnce()
            ->willReturn($this->generateStripeSearchResultMock([]));

        $this->shouldThrow(NotFoundException::class)->during('getProductByKey', ['product_key']);
    }

    public function it_should_get_products_by_metadata(): void
    {
        $this->stripeProductServiceMock->search([
            'query' => "metadata['key']:'product_key' AND active:'true'"
        ])
            ->shouldBeCalledOnce()
            ->willReturn($this->generateStripeSearchResultMock([
                $this->generateStripeProductMock()
            ]));

        $this->getProductsByMetadata(['key' => 'product_key'])
            ->shouldYieldAnInstanceOf(StripeProduct::class);
    }

    public function it_should_get_products_by_metadata_WITH_too_many_metadata_entries(): void
    {
        $this->shouldThrow(new InvalidArgumentException("You can only search for up to 10 metadata keys at a time"))
            ->during('getProductsByMetadata', [
                [
                    'key1' => 'product_key',
                    'key2' => 'product_key',
                    'key3' => 'product_key',
                    'key4' => 'product_key',
                    'key5' => 'product_key',
                    'key6' => 'product_key',
                    'key7' => 'product_key',
                    'key8' => 'product_key',
                    'key9' => 'product_key',
                    'key10' => 'product_key',
                    'key11' => 'product_key',
                ],
                null,
                []
            ]);
    }

    public function it_should_create_product(): void
    {
        $this->configMock->get('tenant_id')
            ->shouldBeCalledTimes(2)
            ->willReturn(1);

        $this->stripeProductServiceMock->create([
            'name' => 'product_name',
            'default_price_data' => [
                'currency' => ProductPriceCurrencyEnum::USD->value,
                'unit_amount' => 999,
                'recurring' => [
                    'interval' => ProductPriceBillingPeriodEnum::MONTHLY->value
                ]
            ],
            'metadata' => [
                'key' => 'tenant:1:1',
                'type' => ProductTypeEnum::SITE_MEMBERSHIP->value,
                'tenant_id' => 1,
                'billing_period' => ProductPriceBillingPeriodEnum::MONTHLY->value,
            ]
        ])
            ->shouldBeCalledOnce()
            ->willReturn($this->generateStripeProductMock());

        $this->createProduct(
            1,
            'product_name',
            999,
            ProductPriceBillingPeriodEnum::MONTHLY,
            ProductPricingModelEnum::RECURRING,
            ProductTypeEnum::SITE_MEMBERSHIP,
            ProductPriceCurrencyEnum::USD,
            null
        )
            ->shouldBeAnInstanceOf(StripeProduct::class);
    }

    public function it_should_create_product_WITH_one_time_fee(): void
    {
        $this->configMock->get('tenant_id')
            ->shouldBeCalledTimes(2)
            ->willReturn(1);

        $this->stripeProductServiceMock->create([
            'name' => 'product_name',
            'default_price_data' => [
                'currency' => ProductPriceCurrencyEnum::USD->value,
                'unit_amount' => 999,
            ],
            'metadata' => [
                'key' => 'tenant:1:1',
                'type' => ProductTypeEnum::SITE_MEMBERSHIP->value,
                'tenant_id' => 1,
                'billing_period' => ProductPriceBillingPeriodEnum::MONTHLY->value,
            ]
        ])
            ->shouldBeCalledOnce()
            ->willReturn($this->generateStripeProductMock());

        $this->createProduct(
            1,
            'product_name',
            999,
            ProductPriceBillingPeriodEnum::MONTHLY,
            ProductPricingModelEnum::ONE_TIME,
            ProductTypeEnum::SITE_MEMBERSHIP,
            ProductPriceCurrencyEnum::USD,
            null
        )
            ->shouldBeAnInstanceOf(StripeProduct::class);
    }
}
