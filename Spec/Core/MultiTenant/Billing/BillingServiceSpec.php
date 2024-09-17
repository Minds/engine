<?php

namespace Spec\Minds\Core\MultiTenant\Billing;

use Minds\Core\Config\Config;
use Minds\Core\Email\V2\Campaigns\Recurring\TenantTrial\TenantTrialEmailer;
use Minds\Core\MultiTenant\AutoLogin\AutoLoginService;
use Minds\Core\MultiTenant\Billing\BillingService;
use Minds\Core\MultiTenant\Enums\TenantPlanEnum;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\MultiTenant\Services\TenantsService;
use Minds\Core\MultiTenant\Services\TenantUsersService;
use Minds\Core\Payments\Checkout\Enums\CheckoutTimePeriodEnum;
use Minds\Core\Payments\Stripe\Checkout\Enums\CheckoutModeEnum;
use Minds\Core\Payments\Stripe\Checkout\Manager as StripeCheckoutManager;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductTypeEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductPriceService as StripeProductPriceService;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;
use Minds\Core\Payments\Stripe\Checkout\Session\Services\SessionService as StripeCheckoutSessionService;
use Minds\Core\Payments\Stripe\CustomerPortal\Services\CustomerPortalService;
use Minds\Core\Payments\Stripe\Subscriptions\Services\SubscriptionsService;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use ReflectionClass;
use Stripe\Price;
use Stripe\Product;
use Stripe\SearchResult;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Subscription;

class BillingServiceSpec extends ObjectBehavior
{
    private Collaborator $stripeCheckoutManagerMock;
    private Collaborator $stripeProductPriceServiceMock;
    private Collaborator $stripeProductServiceMock;
    private Collaborator $stripeCheckoutSessionServiceMock;
    private Collaborator $domainServiceMock;
    private Collaborator $tenantsServiceMock;
    private Collaborator $usersServiceMock;
    private Collaborator $emailServiceMock;
    private Collaborator $stripeSubscriptionsServiceMock;
    private Collaborator $autoLoginServiceMock;
    private Collaborator $customerPortalServiceMock;
    private Collaborator $configMock;
    private Collaborator $multiTenantBootServiceMock;

    private ReflectionClass $stripeProductPriceFactoryMock;

    public function let(
        StripeCheckoutManager        $stripeCheckoutManagerMock,
        StripeProductPriceService    $stripeProductPriceServiceMock,
        StripeProductService         $stripeProductServiceMock,
        StripeCheckoutSessionService $stripeCheckoutSessionServiceMock,
        DomainService                $domainServiceMock,
        TenantsService               $tenantsServiceMock,
        TenantUsersService           $usersServiceMock,
        TenantTrialEmailer           $emailServiceMock,
        SubscriptionsService         $stripeSubscriptionsServiceMock,
        AutoLoginService             $autoLoginServiceMock,
        CustomerPortalService        $customerPortalServiceMock,
        Config                       $configMock,
        MultiTenantBootService       $multiTenantBootServiceMock,
    ) {
        $this->beConstructedWith(
            $stripeCheckoutManagerMock,
            $stripeProductPriceServiceMock,
            $stripeProductServiceMock,
            $stripeCheckoutSessionServiceMock,
            $domainServiceMock,
            $tenantsServiceMock,
            $usersServiceMock,
            $emailServiceMock,
            $stripeSubscriptionsServiceMock,
            $autoLoginServiceMock,
            $customerPortalServiceMock,
            $configMock,
            $multiTenantBootServiceMock,
        );
        $this->stripeCheckoutManagerMock = $stripeCheckoutManagerMock;
        $this->stripeProductPriceServiceMock =   $stripeProductPriceServiceMock;
        $this->stripeProductServiceMock = $stripeProductServiceMock;
        $this->stripeCheckoutSessionServiceMock = $stripeCheckoutSessionServiceMock;
        $this->domainServiceMock = $domainServiceMock;
        $this->tenantsServiceMock = $tenantsServiceMock;
        $this->usersServiceMock = $usersServiceMock;
        $this->emailServiceMock = $emailServiceMock;
        $this->stripeSubscriptionsServiceMock = $stripeSubscriptionsServiceMock;
        $this->autoLoginServiceMock = $autoLoginServiceMock;
        $this->customerPortalServiceMock = $customerPortalServiceMock;
        $this->configMock = $configMock;
        $this->multiTenantBootServiceMock = $multiTenantBootServiceMock;
    
        $this->stripeProductPriceFactoryMock = new ReflectionClass(Price::class);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(BillingService::class);
    }

    public function it_should_create_a_checkout_link_for_external_purchasers(
        SearchResult $stripeProductPricesMock,
    ) {
        $this->stripeProductServiceMock->getProductByKey('networks:community')
            ->shouldBeCalledOnce()
            ->willReturn(new Product('networks:community'));

        $stripeProductPricesMock->getIterator()->willYield([
            $this->generateStripeProductPriceMock(
                id: 'networks:community',
                unitAmount: 1000,
                lookupKey: 'networks:community:monthly',
                type: ProductTypeEnum::NETWORK->value
            )
        ]);

        $this->stripeProductPriceServiceMock->getPricesByProduct('networks:community')
            ->shouldBeCalledOnce()
            ->willReturn($stripeProductPricesMock);
        
        $checkoutSessionMock = new CheckoutSession();
        $checkoutSessionMock->url = 'boo';

        $this->stripeCheckoutManagerMock->createSession(
            null,
            CheckoutModeEnum::SUBSCRIPTION,
            Argument::type('string'),
            Argument::type('string'),
            [
                [
                    'price' => 'networks:community',
                    'quantity' => 1
                ]
            ],
            Argument::any(),
            null,
            [
                'tenant_plan' => 'COMMUNITY',
            ]
        )
            ->shouldBeCalledOnce()
            ->willReturn($checkoutSessionMock);

        $this->createExternalCheckoutLink(TenantPlanEnum::COMMUNITY, CheckoutTimePeriodEnum::MONTHLY)
            ->shouldBe('boo');
    }

    public function it_should_create_the_tenant_on_success(
    ) {
        $checkoutSessionMock = new CheckoutSession();
        $checkoutSessionMock->subscription = 'stripe_subscription_id';
        $checkoutSessionMock->metadata = [
            'tenant_plan' => 'COMMUNITY',
        ];
        $checkoutSessionMock->customer_details = (object) [
            'email' => 'phpspec@minds.com'
        ];

        $this->stripeCheckoutSessionServiceMock->retrieveCheckoutSession('stripe_checkout_id')
            ->shouldBeCalledOnce()
            ->willReturn($checkoutSessionMock);

        $subscriptionMock = new Subscription('sub_test');
        $subscriptionMock->metadata = (object) [
        ];

        $this->stripeSubscriptionsServiceMock->retrieveSubscription('stripe_subscription_id')
            ->shouldBeCalledOnce()
            ->willReturn($subscriptionMock);

        //

        $this->tenantsServiceMock->createNetwork(Argument::that(function ($params) {
            return true;
        }))
            ->willReturn(new Tenant(-1, plan: TenantPlanEnum::COMMUNITY));

        $this->usersServiceMock->createNetworkRootUser(
            Argument::that(function ($params) {
                return true;
            }),
            Argument::that(function ($params) {
                return true;
            })
        );

        //

        $this->emailServiceMock->setUser(Argument::type(User::class))
            ->shouldBeCalledOnce()
            ->willReturn($this->emailServiceMock);

        $this->emailServiceMock->setTenantId(-1)
            ->shouldBeCalledOnce()
            ->willReturn($this->emailServiceMock);

        $this->emailServiceMock->setIsTrial(false)
            ->shouldBeCalledOnce()
            ->willReturn($this->emailServiceMock);

        $this->emailServiceMock->setUsername('networkadmin')
            ->shouldBeCalledOnce()
            ->willReturn($this->emailServiceMock);

        $this->emailServiceMock->setPassword(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($this->emailServiceMock);

        $this->emailServiceMock->send()
            ->shouldBeCalledOnce();

        //

        $this->autoLoginServiceMock->buildLoginUrlWithParamsFromTenant(Argument::type(Tenant::class), Argument::type(User::class))
            ->shouldBeCalledOnce()
            ->willReturn('https://stripe.com/checkout/xyz');

        //

        $this->stripeSubscriptionsServiceMock->updateSubscription('stripe_subscription_id', [
            'tenant_id' => -1,
            'tenant_plan' => 'COMMUNITY'
        ])->shouldBeCalledOnce()
            ->willReturn($subscriptionMock);

        //

        $this->onSuccessfulCheckout('stripe_checkout_id')
            ->shouldBe('https://networks.minds.com/complete-checkout?email=phpspec@minds.com&redirectUrl=https%3A%2F%2Fstripe.com%2Fcheckout%2Fxyz');
    }

    public function it_should_not_create_tenant_if_already_configured(
    ) {
        $checkoutSessionMock = new CheckoutSession();
        $checkoutSessionMock->subscription = 'stripe_subscription_id';
        $checkoutSessionMock->metadata = [
            'tenant_plan' => 'COMMUNITY',
        ];
        $checkoutSessionMock->customer_details = (object) [
            'email' => 'phpspec@minds.com'
        ];

        $this->stripeCheckoutSessionServiceMock->retrieveCheckoutSession('stripe_checkout_id')
            ->shouldBeCalledOnce()
            ->willReturn($checkoutSessionMock);

        $subscriptionMock = new Subscription();
        $subscriptionMock->metadata = (object) [
            'tenant_id' => 1
        ];

        $this->stripeSubscriptionsServiceMock->retrieveSubscription('stripe_subscription_id')
            ->shouldBeCalledOnce()
            ->willReturn($subscriptionMock);

        $this->shouldThrow(ForbiddenException::class)->duringOnSuccessfulCheckout('stripe_checkout_id');
    }

    public function it_should_generate_a_checkout_link_upgrade(
        SearchResult $stripeProductPricesMock
    ) {
        $domain = 'example.minds.com';
        $this->configMock->get('tenant')
            ->willReturn(new Tenant(
                id: 1,
            ));
            
        $this->domainServiceMock->buildNavigatableDomain(Argument::type(Tenant::class))
            ->willReturn($domain);

        $this->stripeProductServiceMock->getProductByKey('networks:team')
            ->shouldBeCalledOnce()
            ->willReturn(new Product('networks:team'));

        $stripeProductPricesMock->getIterator()->willYield([
            $this->generateStripeProductPriceMock(
                id: 'networks:team',
                unitAmount: 1000,
                lookupKey: 'networks:team:monthly',
                type: ProductTypeEnum::NETWORK->value
            )
        ]);
    
        $this->stripeProductPriceServiceMock->getPricesByProduct('networks:team')
            ->shouldBeCalledOnce()
            ->willReturn($stripeProductPricesMock);
        
        $checkoutSessionMock = new CheckoutSession();
        $checkoutSessionMock->url = 'https://stripe.com';
    
        $this->stripeCheckoutManagerMock->createSession(
            null,
            CheckoutModeEnum::SUBSCRIPTION,
            "https://$domain/api/v3/multi-tenant/billing/upgrade-callback?session_id={CHECKOUT_SESSION_ID}",
            "https://networks.minds.com/pricing",
            [
                [
                    'price' => 'networks:team',
                    'quantity' => 1
                ]
            ],
            Argument::any(),
            null,
            [
                'tenant_id' => 1,
                'tenant_plan' => 'TEAM',
            ]
        )
            ->shouldBeCalledOnce()
            ->willReturn($checkoutSessionMock);

        $this->createUpgradeCheckoutLink(TenantPlanEnum::TEAM, CheckoutTimePeriodEnum::MONTHLY, new User())
            ->shouldBe('https://stripe.com');
    }

    public function it_should_return_network_site_link_if_subscription_exists()
    {
        $this->configMock->get('tenant')
            ->willReturn(new Tenant(
                id: 1,
                stripeSubscription: 'sub_test',
            ));

        $this->configMock->get('site_url')
            ->willReturn('https://tenant.phpspec/');

        $userMock = new User();
        $userMock->setEmail('test@minds.com');

        $this->createUpgradeCheckoutLink(TenantPlanEnum::TEAM, CheckoutTimePeriodEnum::MONTHLY, $userMock)
            ->shouldBe('https://networks.minds.com/contact-upgrade?tenant_id=1&plan=TEAM&period=1&email=test%40minds.com');
    }

    public function it_should_upgrade_the_tenant_on_success()
    {
        $this->configMock->get('tenant')
            ->willReturn(new Tenant(id: 1));

        $this->configMock->get('site_url')
            ->willReturn('https://phpspec.minds.com/');
    
        $userMock = new User();

        $checkoutSessionMock = new CheckoutSession();
        $checkoutSessionMock->subscription = 'stripe_subscription_id';
        $checkoutSessionMock->metadata = [
            'tenant_plan' => 'TEAM',
        ];
        $checkoutSessionMock->customer_details = (object) [
            'email' => 'phpspec@minds.com'
        ];

        $this->stripeCheckoutSessionServiceMock->retrieveCheckoutSession('stripe_checkout_id')
            ->shouldBeCalledOnce()
            ->willReturn($checkoutSessionMock);

            
        $subscriptionMock = new Subscription(id: 'stripe_subscription_id');
        $subscriptionMock->metadata = (object) [
        ];

        $this->stripeSubscriptionsServiceMock->retrieveSubscription('stripe_subscription_id')
            ->shouldBeCalledOnce()
            ->willReturn($subscriptionMock);


        $this->tenantsServiceMock->upgradeTenant(Argument::type(Tenant::class), TenantPlanEnum::TEAM, 'stripe_subscription_id', $userMock)
            ->shouldBeCalledOnce();

        $this->onSuccessfulUpgradeCheckout('stripe_checkout_id', $userMock);
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
}
