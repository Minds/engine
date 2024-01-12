<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\Checkout\Services;

use Minds\Core\Payments\Checkout\Delegates\CheckoutEventsDelegate;
use Minds\Core\Payments\Checkout\Enums\CheckoutPageKeyEnum;
use Minds\Core\Payments\Checkout\Enums\CheckoutTimePeriodEnum;
use Minds\Core\Payments\Checkout\Services\CheckoutContentService;
use Minds\Core\Payments\Checkout\Types\AddOn;
use Minds\Core\Payments\Checkout\Types\CheckoutPage;
use Minds\Core\Payments\Checkout\Types\Plan;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductSubTypeEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductTypeEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductPriceService as StripeProductPriceService;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;
use Minds\Core\Strapi\Services\StrapiService;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Psr\SimpleCache\CacheInterface;
use ReflectionClass;
use Stripe\Price;
use Stripe\Product;
use Stripe\SearchResult;

class CheckoutContentServiceSpec extends ObjectBehavior
{
    private Collaborator $strapiServiceMock;
    private Collaborator $stripeProductServiceMock;
    private Collaborator $stripeProductPriceServiceMock;
    private Collaborator $persistentCacheMock;
    private Collaborator $cacheMock;
    private Collaborator $checkoutEventsDelegateMock;

    private ReflectionClass $productMockFactory;
    private ReflectionClass $priceMockFactory;

    public function let(
        StrapiService             $strapiService,
        StripeProductService      $stripeProductService,
        StripeProductPriceService $stripeProductPriceService,
        CacheInterface            $persistentCache,
        CacheInterface            $cache,
        CheckoutEventsDelegate    $checkoutEventsDelegate,
    ): void {
        $this->strapiServiceMock = $strapiService;
        $this->stripeProductServiceMock = $stripeProductService;
        $this->stripeProductPriceServiceMock = $stripeProductPriceService;
        $this->persistentCacheMock = $persistentCache;
        $this->cacheMock = $cache;
        $this->checkoutEventsDelegateMock = $checkoutEventsDelegate;

        $this->productMockFactory = new ReflectionClass(Product::class);
        $this->priceMockFactory = new ReflectionClass(Price::class);

        $this->beConstructedWith(
            $this->strapiServiceMock,
            $this->stripeProductServiceMock,
            $this->stripeProductPriceServiceMock,
            $this->persistentCacheMock,
            $this->cacheMock,
            $this->checkoutEventsDelegateMock,
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(CheckoutContentService::class);
    }

    public function it_should_fetch_checkout_page_with_NO_addons_in_basket(
        SearchResult $stripeProductPricesMock,
        SearchResult $stripeProductAddonsMock,
        SearchResult $stripeProductAddonsPricesMock,
        User         $userMock
    ): void {
        $planId = 'networks:community';

        $userMock->getGuid()->willReturn('user_123');

        #region Stripe's products and prices mocks
        $this->stripeProductServiceMock->getProductByKey($planId)
            ->shouldBecalledOnce()
            ->willReturn(
                $this->generateStripeProductMock(
                    'pr_123',
                    $planId,
                    ProductTypeEnum::NETWORK->value
                )
            );

        $stripeProductPricesMock->getIterator()->willReturn([
            $this->generateStripeProductPriceMock(
                'price_123',
                1000,
                $planId . ":monthly",
                'recurring'
            ),
            $this->generateStripeProductPriceMock(
                'price_456',
                2000,
                $planId . ":yearly",
                'recurring'
            )
        ]);

        $this->stripeProductPriceServiceMock->getPricesByProduct("pr_123")
            ->shouldBecalledOnce()
            ->willReturn($stripeProductPricesMock);

        $stripeProductAddonsMock->getIterator()->willReturn([
            $this->generateStripeProductMock(
                'add_123',
                'mobile_app',
                ProductTypeEnum::NETWORK->value
            )
        ]);

        $this->stripeProductServiceMock->getProductsByType(
            ProductTypeEnum::NETWORK,
            ProductSubTypeEnum::ADDON
        )
            ->shouldBecalledOnce()
            ->willReturn($stripeProductAddonsMock);

        $stripeProductAddonsPricesMock->getIterator()->willReturn([
            $this->generateStripeProductPriceMock(
                'price_789',
                2000,
                $planId . ":mobile_app:monthly",
                'recurring'
            )
        ]);

        $this->stripeProductPriceServiceMock->getPricesByProduct("add_123")
            ->shouldBecalledOnce()
            ->willReturn($stripeProductAddonsPricesMock);
        #endregion

        #region Strapi's products mocks
        $this->strapiServiceMock->getPlan($planId)
            ->shouldBeCalledOnce()
            ->willReturn(new Plan(
                id: 'networks:community',
                name: 'Community',
                description: 'Community plan',
                perksTitle: 'Community perks',
                perks: [
                    'Perk 1',
                    'Perk 2',
                ]
            ));

        $this->strapiServiceMock->getPlanAddons([
            'mobile_app',
        ])
            ->shouldBeCalledOnce()
            ->willYield([
                new AddOn(
                    id: 'mobile_app',
                    name: 'Mobile App',
                    description: 'Mobile App addon',
                    perksTitle: 'Mobile App perks',
                )
            ]);

        $this->strapiServiceMock->getCheckoutPage(CheckoutPageKeyEnum::ADDONS)
            ->shouldBeCalledOnce()
            ->willReturn(new CheckoutPage(
                id: CheckoutPageKeyEnum::ADDONS,
                title: 'Addons',
                description: 'Addons description',
                timePeriod: CheckoutTimePeriodEnum::MONTHLY,
            ));
        #endregion

        $response = $this->getCheckoutPage(
            $planId,
            CheckoutPageKeyEnum::ADDONS,
            CheckoutTimePeriodEnum::MONTHLY,
            $userMock,
            null
        );

        $response->summary->getAddonsSummary()->shouldHaveCount(0);
    }

    private function generateStripeProductMock(
        string $id,
        string $key,
        string $type,
        string $linkedProductKey = null
    ): Product {
        $mock = $this->productMockFactory->newInstanceWithoutConstructor();
        $this->productMockFactory->getProperty('_values')->setValue($mock, [
            'id' => $id,
            'metadata' => [
                'key' => $key,
                'type' => $type,
                'linked_product_key' => $linkedProductKey,
            ],
        ]);

        return $mock;
    }

    private function generateStripeProductPriceMock(
        string $id,
        int    $unitAmount,
        string $lookupKey,
        string $type
    ): Price {
        $mock = $this->priceMockFactory->newInstanceWithoutConstructor();
        $this->priceMockFactory->getProperty('_values')->setValue($mock, [
            'id' => $id,
            'unit_amount' => $unitAmount,
            'lookup_key' => $lookupKey,
            'type' => $type,
        ]);

        return $mock;
    }

    public function it_should_fetch_checkout_page_WITH_addons_in_basket(
        SearchResult $stripeProductPricesMock,
        SearchResult $stripeProductAddonsMock,
        SearchResult $stripeProductAddonsPricesMock,
        User         $userMock
    ): void {
        $planId = 'networks:community';

        $userMock->getGuid()->willReturn('user_123');

        #region Stripe's products and prices mocks
        $this->stripeProductServiceMock->getProductByKey($planId)
            ->shouldBecalledOnce()
            ->willReturn(
                $this->generateStripeProductMock(
                    'pr_123',
                    $planId,
                    ProductTypeEnum::NETWORK->value
                )
            );

        $stripeProductPricesMock->getIterator()->willReturn([
            $this->generateStripeProductPriceMock(
                'price_123',
                1000,
                $planId . ":monthly",
                'recurring'
            ),
            $this->generateStripeProductPriceMock(
                'price_456',
                2000,
                $planId . ":yearly",
                'recurring'
            )
        ]);

        $this->stripeProductPriceServiceMock->getPricesByProduct("pr_123")
            ->shouldBecalledOnce()
            ->willReturn($stripeProductPricesMock);

        $stripeProductAddonsMock->getIterator()->willReturn([
            $this->generateStripeProductMock(
                'add_123',
                'mobile_app',
                ProductTypeEnum::NETWORK->value
            ),
            $this->generateStripeProductMock(
                'add_456',
                'technical_support',
                ProductTypeEnum::NETWORK->value
            )
        ]);

        $this->stripeProductServiceMock->getProductsByType(
            ProductTypeEnum::NETWORK,
            ProductSubTypeEnum::ADDON
        )
            ->shouldBecalledOnce()
            ->willReturn($stripeProductAddonsMock);

        $stripeProductAddonsPricesMock->getIterator()->willReturn([
            $this->generateStripeProductPriceMock(
                'price_789',
                2000,
                $planId . ":mobile_app:monthly",
                'recurring'
            )
        ]);

        $this->stripeProductPriceServiceMock->getPricesByProduct("add_123")
            ->shouldBecalledOnce()
            ->willReturn($stripeProductAddonsPricesMock);

        $stripeProductAddonsPricesMock->getIterator()->willReturn([
            $this->generateStripeProductPriceMock(
                'price_7891',
                2000,
                $planId . ":technical_support:monthly",
                'recurring'
            )
        ]);

        $this->stripeProductPriceServiceMock->getPricesByProduct("add_456")
            ->shouldBecalledOnce()
            ->willReturn($stripeProductAddonsPricesMock);
        #endregion

        #region Strapi's products mocks
        $this->strapiServiceMock->getPlan($planId)
            ->shouldBeCalledOnce()
            ->willReturn(new Plan(
                id: 'networks:community',
                name: 'Community',
                description: 'Community plan',
                perksTitle: 'Community perks',
                perks: [
                    'Perk 1',
                    'Perk 2',
                ]
            ));

        $this->strapiServiceMock->getPlanAddons([
            'mobile_app',
            'technical_support'
        ])
            ->shouldBeCalledOnce()
            ->willYield([
                new AddOn(
                    id: 'mobile_app',
                    name: 'Mobile App',
                    description: 'Mobile App addon',
                    perksTitle: 'Mobile App perks',
                ),
                new AddOn(
                    id: 'technical_support',
                    name: 'Technical Support',
                    description: 'Technical Support addon',
                    perksTitle: 'Technical Support perks',
                )
            ]);

        $this->strapiServiceMock->getCheckoutPage(CheckoutPageKeyEnum::ADDONS)
            ->shouldBeCalledOnce()
            ->willReturn(new CheckoutPage(
                id: CheckoutPageKeyEnum::ADDONS,
                title: 'Addons',
                description: 'Addons description',
                timePeriod: CheckoutTimePeriodEnum::MONTHLY,
            ));
        #endregion

        $response = $this->getCheckoutPage(
            $planId,
            CheckoutPageKeyEnum::ADDONS,
            CheckoutTimePeriodEnum::MONTHLY,
            $userMock,
            ['mobile_app']
        );

        $response->summary->getAddonsSummary()->shouldHaveCount(1);
    }

    public function it_should_fetch_checkout_page_WITH_addons_in_basket_and_linked_products(
        SearchResult $stripeProductPricesMock,
        SearchResult $stripeProductAddonsMock,
        SearchResult $stripeProductAddonsPricesMock,
        User         $userMock
    ): void {
        $planId = 'networks:community';

        $userMock->getGuid()->willReturn('user_123');

        #region Stripe's products and prices mocks
        $this->stripeProductServiceMock->getProductByKey($planId)
            ->shouldBecalledOnce()
            ->willReturn(
                $this->generateStripeProductMock(
                    'pr_123',
                    $planId,
                    ProductTypeEnum::NETWORK->value
                )
            );

        $stripeProductPricesMock->getIterator()->willReturn([
            $this->generateStripeProductPriceMock(
                'price_123',
                1000,
                $planId . ":monthly",
                'recurring'
            ),
            $this->generateStripeProductPriceMock(
                'price_456',
                2000,
                $planId . ":yearly",
                'recurring'
            )
        ]);

        $this->stripeProductPriceServiceMock->getPricesByProduct("pr_123")
            ->shouldBecalledOnce()
            ->willReturn($stripeProductPricesMock);

        $stripeProductAddonsMock->getIterator()->willReturn([
            $this->generateStripeProductMock(
                'add_123',
                'mobile_app',
                ProductTypeEnum::NETWORK->value,
                'mobile_app_setup'
            ),
            $this->generateStripeProductMock(
                'add_456',
                'mobile_app_setup',
                ProductTypeEnum::NETWORK->value,
                'mobile_app'
            )
        ]);

        $this->stripeProductServiceMock->getProductsByType(
            ProductTypeEnum::NETWORK,
            ProductSubTypeEnum::ADDON
        )
            ->shouldBecalledOnce()
            ->willReturn($stripeProductAddonsMock);

        $stripeProductAddonsPricesMock->getIterator()->willReturn([
            $this->generateStripeProductPriceMock(
                'price_789',
                2000,
                $planId . ":mobile_app:monthly",
                'recurring'
            )
        ]);

        $this->stripeProductPriceServiceMock->getPricesByProduct("add_123")
            ->shouldBecalledOnce()
            ->willReturn($stripeProductAddonsPricesMock);

        $stripeProductAddonsPricesMock->getIterator()->willReturn([
            $this->generateStripeProductPriceMock(
                'price_7891',
                2000,
                $planId . ":technical_support:monthly",
                'recurring'
            )
        ]);

        $this->stripeProductPriceServiceMock->getPricesByProduct("add_456")
            ->shouldBecalledOnce()
            ->willReturn($stripeProductAddonsPricesMock);
        #endregion

        #region Strapi's products mocks
        $this->strapiServiceMock->getPlan($planId)
            ->shouldBeCalledOnce()
            ->willReturn(new Plan(
                id: 'networks:community',
                name: 'Community',
                description: 'Community plan',
                perksTitle: 'Community perks',
                perks: [
                    'Perk 1',
                    'Perk 2',
                ]
            ));

        $this->strapiServiceMock->getPlanAddons([
            'mobile_app',
            'mobile_app_setup',
        ])
            ->shouldBeCalledOnce()
            ->willYield([
                new AddOn(
                    id: 'mobile_app',
                    name: 'Mobile App',
                    description: 'Mobile App addon',
                    perksTitle: 'Mobile App perks',
                ),
                new AddOn(
                    id: 'mobile_app_setup',
                    name: 'Mobile App Setup',
                    description: 'Mobile App Setup addon',
                    perksTitle: 'Mobile App Setup perks',
                )
            ]);

        $this->strapiServiceMock->getCheckoutPage(CheckoutPageKeyEnum::ADDONS)
            ->shouldBeCalledOnce()
            ->willReturn(new CheckoutPage(
                id: CheckoutPageKeyEnum::ADDONS,
                title: 'Addons',
                description: 'Addons description',
                timePeriod: CheckoutTimePeriodEnum::MONTHLY,
            ));
        #endregion

        $response = $this->getCheckoutPage(
            $planId,
            CheckoutPageKeyEnum::ADDONS,
            CheckoutTimePeriodEnum::MONTHLY,
            $userMock,
            ['mobile_app']
        );

        $response->summary->getAddonsSummary()->shouldHaveCount(2);
    }
}
