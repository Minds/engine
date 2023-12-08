<?php

namespace Minds\Core\Payments\Stripe\Checkout;

use InvalidArgumentException;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Payments\Stripe\Checkout\Enums\CheckoutModeEnum;
use Minds\Core\Payments\Stripe\Customers;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Entities\User;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;

class Manager
{
    public function __construct(
        private ?StripeClient           $stripeClient = null,
        private ?Customers\ManagerV2 $customersManager = null,
        private ?Config            $config = null
    ) {
        $this->config ??= Di::_()->get('Config');
        $this->stripeClient ??= Di::_()->get(StripeClient::class);
        $this->customersManager ??= new Customers\ManagerV2();
    }

    /**
     * Creates a checkout session
     * https://stripe.com/docs/api/checkout/sessions/create
     * @param User $user - the user we are creating the session for
     * @param string|CheckoutModeEnum $mode - defaults to setup
     * @param string|null $successUrl
     * @param array $lineItems
     * @return Session
     * @throws ApiErrorException
     */
    public function createSession(
        User $user,
        string|CheckoutModeEnum $mode = 'setup',
        ?string $successUrl = null,
        array $lineItems = [],
        ?array $paymentMethodTypes = null
    ): Session {
        $customerId = $this->customersManager->getByUser($user)->id;

        if (is_string($mode)) {
            $mode = CheckoutModeEnum::tryFrom($mode);

            if (!$mode) {
                throw new InvalidArgumentException('Invalid checkout mode provided');
            }
        }

        $checkoutOptions = [
            'success_url' => $successUrl ?? $this->getSiteUrl() . 'api/v3/payments/stripe/checkout/success',
            'cancel_url' => $this->getSiteUrl() . 'api/v3/payments/stripe/checkout/cancel',
            'mode' => $mode->value,
            'payment_method_types' => $paymentMethodTypes ?? [ 'card' ], // we can possibly add more in the future,
            'customer' => $customerId,
        ];

        if ($mode === CheckoutModeEnum::SUBSCRIPTION) {
            $checkoutOptions['line_items'] = $lineItems;
        }

        return $this->stripeClient->checkout->sessions->create($checkoutOptions);
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
