<?php

namespace Spec\Minds\Core\Payments\Stripe\Checkout;

use Minds\Core\Config\Config;
use Minds\Core\Payments\Stripe\Checkout\Manager;
use Minds\Core\Payments\Stripe\Customers;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Stripe;

class ManagerSpec extends ObjectBehavior
{
    private $configMock;
    private $stripeClientMock;
    private $customerManagerMock;

    public function let(
        Config $config,
        StripeClient $stripeClient,
        Customers\ManagerV2 $customerManager
    ) {
        $this->beConstructedWith($stripeClient, $customerManager, $config);

        $this->configMock = $config;
        $this->stripeClientMock = $stripeClient;
        $this->customerManagerMock = $customerManager;

        $config->get('site_url')->willReturn('https://spec.minds.io/');
    }
    
    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_create_session(
        User $user,
        Stripe\Customer $stripeCustomerMock,
        Stripe\Service\Checkout\CheckoutServiceFactory $stripeCheckoutMock,
        Stripe\Service\Checkout\SessionService $stripeCheckoutSessionMock,
    ) {
        $this->customerManagerMock->getByUser($user)->willReturn($stripeCustomerMock);

        $this->stripeClientMock->checkout = $stripeCheckoutMock;
        $stripeCheckoutMock->sessions = $stripeCheckoutSessionMock;

        $stripeCheckoutSessionMock->create(Argument::any())
            ->willReturn(new Stripe\Checkout\Session);

        $this->createSession($user);
    }
}
