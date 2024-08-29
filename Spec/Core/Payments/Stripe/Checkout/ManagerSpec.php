<?php

namespace Spec\Minds\Core\Payments\Stripe\Checkout;

use Minds\Core\Config\Config;
use Minds\Core\Payments\Stripe\Checkout\Enums\CheckoutModeEnum;
use Minds\Core\Payments\Stripe\Checkout\Enums\PaymentMethodCollectionEnum;
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

    public function it_should_create_session_with_all_params(
        User $user,
        Stripe\Customer $stripeCustomerMock,
        Stripe\Service\Checkout\CheckoutServiceFactory $stripeCheckoutMock,
        Stripe\Service\Checkout\SessionService $stripeCheckoutSessionMock,
    ) {
        $mode = CheckoutModeEnum::PAYMENT;
        $successUrl = 'https://example.minds.com/checkout/success';
        $cancelUrl = 'https://example.minds.com/checkout/cancel';
        $lineItems = [
            [
                'price' => 'price_123',
                'quantity' => 1
            ]
        ];
        $paymentMethodTypes = ['card'];
        $submitMessage = 'Pay now';
        $metadata = [
            'order_id' => '12345'
        ];
        $phoneNumberCollection = true;
        $subscriptionData = [
            'trial_period_days' => 14
        ];
        $paymentMethodCollection = PaymentMethodCollectionEnum::IF_REQUIRED;
        $customFields = [
            [
                'key' => 'customer_name',
                'label' => [
                    'type' => 'custom',
                    'custom' => 'Your Name'
                ],
                'type' => 'text',
                'required' => true
            ]
        ];

        $this->customerManagerMock->getByUser($user)->willReturn($stripeCustomerMock);

        $this->stripeClientMock->checkout = $stripeCheckoutMock;
        $stripeCheckoutMock->sessions = $stripeCheckoutSessionMock;

        $stripeCheckoutSessionMock->create(Argument::that(function ($args) use (
            $mode,
            $successUrl,
            $cancelUrl,
            $lineItems,
            $paymentMethodTypes,
            $submitMessage,
            $metadata,
            $phoneNumberCollection,
            $subscriptionData,
            $paymentMethodCollection,
            $customFields
        ) {
            return $args['mode'] === $mode->value &&
                $args['success_url'] === $successUrl &&
                $args['cancel_url'] === $cancelUrl &&
                $args['line_items'] === $lineItems &&
                $args['payment_method_types'] === $paymentMethodTypes &&
                $args['custom_text']['submit']['message'] === $submitMessage &&
                $args['metadata'] === $metadata &&
                $args['phone_number_collection']['enabled'] === $phoneNumberCollection &&
                $args['subscription_data'] === $subscriptionData &&
                $args['payment_method_collection'] === $paymentMethodCollection->value &&
                $args['custom_fields'] === $customFields;
        }))
            ->willReturn(new Stripe\Checkout\Session);

        $this->createSession(
            $user,
            $mode,
            $successUrl,
            $cancelUrl,
            $lineItems,
            $paymentMethodTypes,
            $submitMessage,
            $metadata,
            $phoneNumberCollection,
            $subscriptionData,
            $paymentMethodCollection,
            $customFields
        );
    }
}
