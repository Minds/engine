<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\Config\Config;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipSubscriptionsRepository;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsManagementService;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembershipSubscription;
use Minds\Core\Payments\Stripe\CustomerPortal\Services\CustomerPortalService as StripeCustomerPortalService;
use Minds\Core\Payments\Stripe\Subscriptions\Services\SubscriptionsService as StripeSubscriptionsService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use ReflectionClass;
use Stripe\BillingPortal\Session as StripeCustomerPortalSession;
use Stripe\Subscription as StripeSubscription;

class SiteMembershipSubscriptionsManagementServiceSpec extends ObjectBehavior
{
    private Collaborator $siteMembershipSubscriptionsRepositoryMock;
    private Collaborator $stripeSubscriptionsServiceMock;
    private Collaborator $stripeCustomerPortalServiceMock;
    private Collaborator $configMock;

    private ReflectionClass $siteMembershipSubscriptionMockFactory;
    private ReflectionClass $stripeCustomerPortalSessionMockFactory;
    private ReflectionClass $stripeSubscriptionMockFactory;

    public function let(
        SiteMembershipSubscriptionsRepository $siteMembershipSubscriptionsRepository,
        StripeSubscriptionsService            $stripeSubscriptionsService,
        StripeCustomerPortalService           $stripeCustomerPortalService,
        Config                                $config
    ): void {
        $this->siteMembershipSubscriptionsRepositoryMock = $siteMembershipSubscriptionsRepository;
        $this->stripeSubscriptionsServiceMock = $stripeSubscriptionsService;
        $this->stripeCustomerPortalServiceMock = $stripeCustomerPortalService;
        $this->configMock = $config;

        $this->siteMembershipSubscriptionMockFactory = new ReflectionClass(SiteMembershipSubscription::class);
        $this->stripeCustomerPortalSessionMockFactory = new ReflectionClass(StripeCustomerPortalSession::class);
        $this->stripeSubscriptionMockFactory = new ReflectionClass(StripeSubscription::class);

        $this->beConstructedWith(
            $this->siteMembershipSubscriptionsRepositoryMock,
            $this->stripeSubscriptionsServiceMock,
            $this->stripeCustomerPortalServiceMock,
            $this->configMock
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(SiteMembershipSubscriptionsManagementService::class);
    }

    public function it_should_generate_a_manage_site_membership_subscription_stripe_link(): void
    {
        $this->siteMembershipSubscriptionsRepositoryMock->getSiteMembershipSubscriptionById(1)
            ->shouldBeCalledOnce()
            ->willReturn(
                $this->generateSiteMembershipSubscriptionMock(
                    1,
                    'sub_123',
                    true
                )
            );

        $this->configMock->get('site_url')
            ->shouldBeCalledTimes(2)
            ->willReturn('https://example.com/');

        $this->stripeSubscriptionsServiceMock->retrieveSubscription('sub_123')
            ->shouldBeCalledOnce()
            ->willReturn(
                $this->generateStripeSubscriptionMock(
                    stripeSubscriptionId: 'sub_123',
                    stripeSubscriptionCustomerId: 'cus_123'
                )
            );

        $this->stripeCustomerPortalServiceMock->createCustomerPortalSession(
            'cus_123',
            'https://example.com/memberships',
            [
                'type' => 'subscription_cancel',
                'after_completion' => [
                    'type' => 'redirect',
                    'redirect' => [
                        'return_url' => 'https://example.com/api/v3/payments/site-memberships/subscriptions/1/manage/cancel?redirectPath=/memberships',
                    ]
                ],
                'subscription_cancel' => [
                    'subscription' => 'sub_123'
                ]
            ]
        )
            ->shouldBeCalledOnce()
            ->willReturn('https://stripe.com/checkout');

        $this->generateManageSiteMembershipSubscriptionLink(
            1,
            '/memberships'
        )
            ->shouldReturn('https://stripe.com/checkout');
    }

    private function generateSiteMembershipSubscriptionMock(
        int    $membershipSubscriptionId,
        string $stripeSubscriptionId,
        bool   $autoRenew
    ): SiteMembershipSubscription {
        $siteMembershipSubscriptionMock = $this->siteMembershipSubscriptionMockFactory->newInstanceWithoutConstructor();
        $this->siteMembershipSubscriptionMockFactory->getProperty('membershipSubscriptionId')->setValue($siteMembershipSubscriptionMock, $membershipSubscriptionId);
        $this->siteMembershipSubscriptionMockFactory->getProperty('stripeSubscriptionId')->setValue($siteMembershipSubscriptionMock, $stripeSubscriptionId);
        $this->siteMembershipSubscriptionMockFactory->getProperty('autoRenew')->setValue($siteMembershipSubscriptionMock, $autoRenew);

        return $siteMembershipSubscriptionMock;
    }

    private function generateStripeSubscriptionMock(
        string $stripeSubscriptionId,
        string $stripeSubscriptionCustomerId
    ): StripeSubscription {
        $stripeSubscriptionMock = $this->stripeSubscriptionMockFactory->newInstanceWithoutConstructor();
        $this->stripeSubscriptionMockFactory->getProperty('_values')->setValue($stripeSubscriptionMock, [
            'id' => $stripeSubscriptionId,
            'customer' => $stripeSubscriptionCustomerId

        ]);

        return $stripeSubscriptionMock;
    }

    public function it_should_complete_site_membership_cancellation(): void
    {
        $this->siteMembershipSubscriptionsRepositoryMock->setSiteMembershipSubscriptionAutoRenew(1, false)
            ->shouldBeCalledOnce();

        $this->completeSiteMembershipCancellation(1);
    }

}
