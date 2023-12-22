<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\Checkout\Services;

use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\TenantsService;
use Minds\Core\Payments\Checkout\Enums\CheckoutTimePeriodEnum;
use Minds\Core\Payments\Checkout\Services\CheckoutService;
use Minds\Core\Payments\Stripe\Checkout\Enums\CheckoutModeEnum;
use Minds\Core\Payments\Stripe\Checkout\Manager as StripeCheckoutManager;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductSubTypeEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductTypeEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductPriceService as StripeProductPriceService;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;
use Minds\Core\Payments\Stripe\Checkout\Session\Services\SessionService as StripeCheckoutSessionService;
use Minds\Core\Payments\Stripe\Subscriptions\Services\SubscriptionsService;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Psr\SimpleCache\CacheInterface;
use ReflectionClass;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Price;
use Stripe\Product;
use Stripe\SearchResult;

class CheckoutServiceSpec extends ObjectBehavior
{
    private Collaborator $stripeCheckoutManagerMock;
    private Collaborator $stripeProductPriceServiceMock;
    private Collaborator $stripeProductServiceMock;
    private Collaborator $stripeCheckoutSessionServiceMock;
    private Collaborator $tenantsServiceMock;
    private Collaborator $subscriptionsServiceMock;
    private Collaborator $cacheMock;

    private ReflectionClass $stripeProductFactoryMock;
    private ReflectionClass $stripeProductPriceFactoryMock;
    private ReflectionClass $stripeCheckoutSessionFactoryMock;

    public function let(
        StripeCheckoutManager        $stripeCheckoutManager,
        StripeProductPriceService    $stripeProductPriceService,
        StripeProductService         $stripeProductService,
        StripeCheckoutSessionService $stripeCheckoutSessionService,
        TenantsService               $tenantsService,
        SubscriptionsService         $stripeSubscriptionsService,
        CacheInterface               $cache,
    ): void {
        $this->stripeCheckoutManagerMock = $stripeCheckoutManager;
        $this->stripeProductPriceServiceMock = $stripeProductPriceService;
        $this->stripeProductServiceMock = $stripeProductService;
        $this->stripeCheckoutSessionServiceMock = $stripeCheckoutSessionService;
        $this->tenantsServiceMock = $tenantsService;
        $this->subscriptionsServiceMock = $stripeSubscriptionsService;
        $this->cacheMock = $cache;

        $this->stripeProductFactoryMock = new ReflectionClass(Product::class);
        $this->stripeProductPriceFactoryMock = new ReflectionClass(Price::class);
        $this->stripeCheckoutSessionFactoryMock = new ReflectionClass(CheckoutSession::class);

        $this->beConstructedWith(
            $this->stripeCheckoutManagerMock,
            $this->stripeProductPriceServiceMock,
            $this->stripeProductServiceMock,
            $this->stripeCheckoutSessionServiceMock,
            $this->tenantsServiceMock,
            $this->subscriptionsServiceMock,
            $this->cacheMock,
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(CheckoutService::class);
    }

    public function it_should_generate_checkout_link(
        User         $userMock,
        SearchResult $stripeProductPricesMock,
        SearchResult $stripeProductAddonsMock,
        SearchResult $stripeProductAddonsPricesMock,
    ): void {
        $userMock->getGuid()->willReturn('user-guid');

        $this->stripeProductServiceMock->getProductByKey('plan-id')
            ->shouldBeCalledOnce()
            ->willReturn(
                $this->generateStripeProductMock(
                    id: 'plan-id',
                    key: 'plan-id',
                    type: ProductTypeEnum::NETWORK->value
                )
            );

        $stripeProductPricesMock->getIterator()->willYield([
            $this->generateStripeProductPriceMock(
                id: 'plan-id',
                unitAmount: 1000,
                lookupKey: 'plan-id:yearly',
                type: ProductTypeEnum::NETWORK->value
            )
        ]);

        $this->stripeProductPriceServiceMock->getPricesByProduct('plan-id')
            ->shouldBeCalledOnce()
            ->willReturn($stripeProductPricesMock);

        $stripeProductAddonsMock->getIterator()->willYield([
            $this->generateStripeProductMock(
                id: 'add-on-id',
                key: 'add-on-id',
                type: ProductTypeEnum::NETWORK->value
            )
        ]);

        $this->stripeProductServiceMock->getProductsByType(
            ProductTypeEnum::NETWORK,
            ProductSubTypeEnum::ADDON
        )
            ->shouldBeCalledOnce()
            ->willReturn($stripeProductAddonsMock);

        $this->stripeCheckoutManagerMock->createSession(
            $userMock,
            CheckoutModeEnum::SUBSCRIPTION,
            "api/v3/payments/checkout/complete?session_id={CHECKOUT_SESSION_ID}",
            "networks/checkout?planId=plan-id&timePeriod=yearly",
            Argument::type('array'),
            ['card', 'us_bank_account'],
            Argument::type('string'),
        )
            ->shouldBeCalledOnce()
            ->willReturn($this->generateStripeCheckoutSessionMock());

        $stripeProductAddonsPricesMock->getIterator()->willYield([
            $this->generateStripeProductPriceMock(
                id: 'add-on-id',
                unitAmount: 100,
                lookupKey: 'add-on-id:yearly',
                type: "recurring"
            )
        ]);

        $this->stripeProductPriceServiceMock->getPricesByProduct('add-on-id')
            ->shouldBeCalledOnce()
            ->willReturn($stripeProductAddonsPricesMock);

        $this->generateCheckoutLink(
            $userMock,
            'plan-id',
            CheckoutTimePeriodEnum::YEARLY,
            ['add-on-id']
        )->shouldBeString();
    }

    private function generateStripeProductMock(
        string $id,
        string $key,
        string $type
    ): Product {
        $mock = $this->stripeProductFactoryMock->newInstanceWithoutConstructor();
        $this->stripeProductFactoryMock->getProperty('_values')->setValue($mock, [
            'id' => $id,
            'metadata' => [
                'key' => $key,
                'type' => $type
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
        $mock = $this->stripeProductPriceFactoryMock->newInstanceWithoutConstructor();
        $this->stripeProductPriceFactoryMock->getProperty('_values')->setValue($mock, [
            'id' => $id,
            'unit_amount' => $unitAmount,
            'lookup_key' => $lookupKey,
            'type' => $type,
        ]);

        return $mock;
    }

    private function generateStripeCheckoutSessionMock(): CheckoutSession
    {
        $mock = $this->stripeCheckoutSessionFactoryMock->newInstanceWithoutConstructor();
        $this->stripeCheckoutSessionFactoryMock->getProperty('_values')->setValue($mock, [
            'id' => 'checkout-session-id',
            'url' => 'cs_test_123',
            'subscription' => 'sub_test_123',
        ]);

        return $mock;
    }

    public function it_should_complete_checkout(
        User $userMock
    ): void {
        $userMock->getGuid()->willReturn('user-guid');
        $this->stripeCheckoutSessionServiceMock->retrieveCheckoutSession('checkout-session-id')
            ->shouldBeCalledOnce()
            ->willReturn($this->generateStripeCheckoutSessionMock());

        $this->tenantsServiceMock->createNetwork(Argument::type(Tenant::class))
            ->shouldBeCalledOnce()
            ->willReturn(new Tenant(
                id: 1,
                ownerGuid: 1,
            ));

        $this->subscriptionsServiceMock->updateSubscription(
            'sub_test_123',
            [
                'tenant_id' => 1,
            ]
        )
            ->shouldBeCalledOnce();

        $this->completeCheckout($userMock, 'checkout-session-id');
    }
}
