<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipPricingModelEnum;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipSubscriptionsRepository;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipReaderService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsService;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembershipSubscription;
use Minds\Core\Payments\Stripe\Checkout\Enums\CheckoutModeEnum;
use Minds\Core\Payments\Stripe\Checkout\Manager as StripeCheckoutManager;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;
use Minds\Core\Payments\Stripe\Checkout\Session\Services\SessionService as StripeCheckoutSessionService;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use ReflectionClass;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Product as StripeProduct;
use Minds\Core\Groups\V2\Membership\Manager as GroupMembershipService;
use Minds\Core\Payments\SiteMemberships\Repositories\DTO\SiteMembershipSubscriptionDTO;
use Prophecy\Argument;

class SiteMembershipSubscriptionsServiceSpec extends ObjectBehavior
{
    private Collaborator $siteMembershipSubscriptionsRepositoryMock;
    private Collaborator $siteMembershipReaderServiceMock;
    private Collaborator $stripeCheckoutManagerMock;
    private Collaborator $stripeProductServiceMock;
    private Collaborator $stripeCheckoutSessionServiceMock;
    private Collaborator $configMock;
    private Collaborator $groupMembershipServiceMock;
    private Collaborator $entitiesBuilderMock;

    private ReflectionClass $siteMembershipSubscriptionMockFactory;
    private ReflectionClass $siteMembershipMockFactory;
    private ReflectionClass $stripeCheckoutSessionMockFactory;
    private ReflectionClass $stripeProductMockFactory;

    public function let(
        SiteMembershipSubscriptionsRepository $siteMembershipSubscriptionsRepository,
        SiteMembershipReaderService           $siteMembershipReaderService,
        StripeCheckoutManager                 $stripeCheckoutManager,
        StripeProductService                  $stripeProductService,
        StripeCheckoutSessionService          $stripeCheckoutSessionService,
        Config                                $config,
        GroupMembershipService                $groupMembershipServiceMock,
        EntitiesBuilder                       $entitiesBuilderMock,
    ): void {
        $this->siteMembershipSubscriptionsRepositoryMock = $siteMembershipSubscriptionsRepository;
        $this->siteMembershipReaderServiceMock = $siteMembershipReaderService;
        $this->stripeCheckoutManagerMock = $stripeCheckoutManager;
        $this->stripeProductServiceMock = $stripeProductService;
        $this->stripeCheckoutSessionServiceMock = $stripeCheckoutSessionService;
        $this->configMock = $config;
        $this->groupMembershipServiceMock = $groupMembershipServiceMock;
        $this->entitiesBuilderMock = $entitiesBuilderMock;

        $this->siteMembershipSubscriptionMockFactory = new ReflectionClass(SiteMembershipSubscription::class);
        $this->siteMembershipMockFactory = new ReflectionClass(SiteMembership::class);
        $this->stripeCheckoutSessionMockFactory = new ReflectionClass(StripeCheckoutSession::class);
        $this->stripeProductMockFactory = new ReflectionClass(StripeProduct::class);

        $this->beConstructedWith(
            $this->siteMembershipSubscriptionsRepositoryMock,
            $this->siteMembershipReaderServiceMock,
            $this->stripeCheckoutManagerMock,
            $this->stripeProductServiceMock,
            $this->stripeCheckoutSessionServiceMock,
            $this->configMock,
            $this->groupMembershipServiceMock,
            $this->entitiesBuilderMock,
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(SiteMembershipSubscriptionsService::class);
    }

    public function it_should_get_membership_checkout_link_with_subscription_mode(
        User $userMock
    ): void {
        $this->siteMembershipSubscriptionsRepositoryMock->getSiteMembershipSubscriptionByMembershipGuid(
            membershipGuid: 1,
            user: $userMock
        )->willReturn(null);

        $this->siteMembershipReaderServiceMock->getSiteMembership(1)
            ->shouldBeCalled()
            ->willReturn($this->generateSiteMembershipMock(1, "prod_123", SiteMembershipPricingModelEnum::RECURRING));

        $this->stripeProductServiceMock->getProductById(
            "prod_123"
        )
            ->shouldBeCalledOnce()
            ->willReturn($this->generateStripeProductMock("prod_123", "price_123"));

        $this->stripeCheckoutManagerMock->createSession(
            $userMock,
            CheckoutModeEnum::SUBSCRIPTION,
            "api/v3/payments/site-memberships/1/checkout/complete?session_id={CHECKOUT_SESSION_ID}",
            "memberships",
            [
                [
                    'price' => 'price_123',
                    'quantity' => 1
                ]
            ],
            [
                'card'
            ],
            null,
            [
                'redirectPath' => '/memberships',
                'siteMembershipGuid' => '1'
            ]
        )
            ->shouldBeCalledOnce()
            ->willReturn($this->generateStripeCheckoutSessionMock("https://stripe.com/checkout"));

        $this->getCheckoutLink(
            1,
            $userMock,
            "/memberships"
        )
            ->shouldReturn("https://stripe.com/checkout");
    }

    private function generateSiteMembershipMock(
        int                            $siteMembershipGuid,
        string                         $stripeProductId,
        SiteMembershipPricingModelEnum $membershipPricingModel,
        array                          $groups = [],
    ): SiteMembership {
        $siteMembershipMock = $this->siteMembershipMockFactory->newInstanceWithoutConstructor();
        $this->siteMembershipMockFactory->getProperty('membershipGuid')->setValue($siteMembershipMock, $siteMembershipGuid);
        $this->siteMembershipMockFactory->getProperty('stripeProductId')->setValue($siteMembershipMock, $stripeProductId);
        $this->siteMembershipMockFactory->getProperty('membershipPricingModel')->setValue($siteMembershipMock, $membershipPricingModel);
        $this->siteMembershipMockFactory->getProperty('groups')->setValue($siteMembershipMock, $groups);

        return $siteMembershipMock;
    }

    private function generateStripeProductMock(
        string $stripeProductId,
        string $stripeProductPriceId
    ): StripeProduct {
        $stripeProductMock = $this->stripeProductMockFactory->newInstanceWithoutConstructor();
        $this->stripeProductMockFactory->getProperty('_values')->setValue($stripeProductMock, [
            'id' => $stripeProductId,
            'default_price' => $stripeProductPriceId,
        ]);

        return $stripeProductMock;
    }

    private function generateStripeCheckoutSessionMock(
        string $url
    ): StripeCheckoutSession {
        $stripeCheckoutSessionMock = $this->stripeCheckoutSessionMockFactory->newInstanceWithoutConstructor();
        $this->stripeCheckoutSessionMockFactory->getProperty('_values')->setValue($stripeCheckoutSessionMock, [
            'url' => $url,
            'subscription' => 'sub_123',
            'payment_intent' => 'pi_123',
            'metadata' => [
                'siteMembershipGuid' => '1',
                'redirectPath' => '/memberships'
            ]
        ]);

        return $stripeCheckoutSessionMock;
    }

    public function it_should_get_membership_checkout_link_with_payment_mode(
        User $userMock
    ): void {
        $this->siteMembershipSubscriptionsRepositoryMock->getSiteMembershipSubscriptionByMembershipGuid(
            membershipGuid: 1,
            user: $userMock
        )->willReturn(null);

        $this->siteMembershipReaderServiceMock->getSiteMembership(1)
            ->shouldBeCalled()
            ->willReturn($this->generateSiteMembershipMock(1, "prod_123", SiteMembershipPricingModelEnum::ONE_TIME));

        $this->stripeProductServiceMock->getProductById(
            "prod_123"
        )
            ->shouldBeCalledOnce()
            ->willReturn($this->generateStripeProductMock("prod_123", "price_123"));

        $this->stripeCheckoutManagerMock->createSession(
            $userMock,
            CheckoutModeEnum::PAYMENT,
            "api/v3/payments/site-memberships/1/checkout/complete?session_id={CHECKOUT_SESSION_ID}",
            "memberships",
            [
                [
                    'price' => 'price_123',
                    'quantity' => 1
                ]
            ],
            [
                'card'
            ],
            null,
            [
                'redirectPath' => '/memberships',
                'siteMembershipGuid' => '1'
            ]
        )
            ->shouldBeCalledOnce()
            ->willReturn($this->generateStripeCheckoutSessionMock("https://stripe.com/checkout"));

        $this->getCheckoutLink(
            1,
            $userMock,
            "/memberships"
        )
            ->shouldReturn("https://stripe.com/checkout");
    }

    public function it_should_redirect_to_final_uri_when_subscription_exists(
        User $userMock
    ): void {
        $this->configMock->get('site_url')
            ->shouldBeCalled()
            ->willReturn("https://minds.com/");

        $this->siteMembershipSubscriptionsRepositoryMock->getSiteMembershipSubscriptionByMembershipGuid(
            membershipGuid: 1,
            user: $userMock
        )->willReturn(
            $this->siteMembershipSubscriptionMockFactory->newInstanceWithoutConstructor()
        );

        $this->getCheckoutLink(
            1,
            $userMock,
            "/memberships"
        )
            ->shouldReturn("https://minds.com/memberships?error=SUBSCRIPTION_ALREADY_EXISTS");
    }

    public function it_should_complete_site_membership_checkout(
        User $userMock
    ): void {
        $this->stripeCheckoutSessionServiceMock->retrieveCheckoutSession(
            "checkout_session_id"
        )
            ->shouldBeCalledOnce()
            ->willReturn($this->generateStripeCheckoutSessionMock("https://stripe.com/checkout"));

        $siteMembershipMock = $this->generateSiteMembershipMock(1, "prod_123", SiteMembershipPricingModelEnum::RECURRING);

        $this->siteMembershipReaderServiceMock->getSiteMembership(1)
            ->shouldBeCalled()
            ->willReturn($siteMembershipMock);

        $this->siteMembershipSubscriptionsRepositoryMock->storeSiteMembershipSubscription(
            Argument::type(SiteMembershipSubscriptionDTO::class)
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->completeSiteMembershipCheckout(
            "checkout_session_id",
            $userMock
        )
            ->shouldReturn("/memberships");
    }

    public function it_should_get_all_site_memberships()
    {
        $tenantId = 123;

        $this->siteMembershipSubscriptionsRepositoryMock->getAllSiteMembershipSubscriptions($tenantId)
            ->shouldBeCalled()
            ->willReturn([]);

        $this->getAllSiteMemberships($tenantId)->shouldBe([]);
    }

    public function it_should_get_site_membership_subscription_by_stripe_subscription_id()
    {
        $subscriptionId = 'sub_id';

        $siteMembershipSubscription = new SiteMembershipSubscription(
            456,
            1,
            1,
            $subscriptionId,
        );

        $this->siteMembershipSubscriptionsRepositoryMock->getSiteMembershipSubscriptionByStripeSubscriptionId($subscriptionId)
            ->shouldBeCalled()
            ->willReturn($siteMembershipSubscription);

        $this->getSiteMembershipSubscriptionByStripeSubscriptionId($subscriptionId)->shouldBe($siteMembershipSubscription);
    }

    public function it_should_renew_site_subscriptions()
    {
        $subscriptionId = 'sub_id';
        $startTime = time();
        $endTime = strtotime('+1 year', $startTime);

        $this->siteMembershipSubscriptionsRepositoryMock->renewSiteMembershipSubscription(
            $subscriptionId,
            $startTime,
            $endTime
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->renewSiteMembershipSubscription($subscriptionId, $startTime, $endTime)->shouldBe(true);
    }

    public function it_should_sync_out_of_sync_site_memberships(SiteMembership $siteMembershipMock)
    {
        $this->siteMembershipSubscriptionsRepositoryMock->getOutOfSyncSiteMemberships()
            ->willReturn([
                new SiteMembershipSubscription(
                    456,
                    1,
                    1,
                    null,
                )
            ]);

        $user1 = new User();

        $this->entitiesBuilderMock->single(456)
            ->willReturn($user1);

        $this->siteMembershipReaderServiceMock->getSiteMembership(1)
            ->willReturn($siteMembershipMock);

        $siteMembershipMock->getGroups()
            ->willReturn([]);

        $this->syncOutOfSyncSiteMemberships();
    }
}
