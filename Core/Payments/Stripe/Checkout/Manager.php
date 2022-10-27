<?php

namespace Minds\Core\Payments\Stripe\Checkout;

use Minds\Core\Payments\Stripe\Customers;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Entities\User;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

class Manager
{
    public function __construct(
        private ?StripeClient           $stripeClient = null,
        private ?Customers\ManagerV2 $customersManager = null,
        private ?Config            $config = null
    ) {
        $this->config ??= Di::_()->get('Config');
        $this->stripeClient ??= new StripeClient($this->config->get('payments')['stripe']['api_key']);
        $this->customersManager ??= new Customers\ManagerV2();
    }

    /**
     * Creates a checkout session
     * https://stripe.com/docs/api/checkout/sessions/create
     * @param User $user - the user we are creating the session for
     * @param string $mode - defaults to setup
     * @return Session
     */
    public function createSession(User $user, string $mode = 'setup'): Session
    {
        $customerId = $this->customersManager->getByUser($user)->id;

        return $this->stripeClient->checkout->sessions->create([
            'success_url' => $this->getSiteUrl() . 'api/v3/payments/stripe/checkout/success',
            'cancel_url' => $this->getSiteUrl() . 'api/v3/payments/stripe/checkout/cancel',
            'mode' => $mode,
            'payment_method_types' => [ 'card' ], // we can possibly add more in the future,
            'customer' => $customerId,
          ]);
    }

    /**
     * Helper to get the site url
     * @return string
     */
    protected function getSiteUrl(): string
    {
        return $this->config->get('site_url');
    }
}
