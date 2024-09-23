<?php

namespace Minds\Core\Payments\Stripe\Checkout;

use InvalidArgumentException;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Payments\Stripe\Checkout\Enums\CheckoutModeEnum;
use Minds\Core\Payments\Stripe\Checkout\Enums\PaymentMethodCollectionEnum;
use Minds\Core\Payments\Stripe\Checkout\Models\CustomField;
use Minds\Core\Payments\Stripe\Customers;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Entities\User;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;

class Manager
{
    public function __construct(
        private ?StripeClient        $stripeClient = null,
        private ?Customers\ManagerV2 $customersManager = null,
        private ?Config              $config = null
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
     * @param string|null $cancelUrl
     * @param array $lineItems
     * @param array|null $paymentMethodTypes
     * @param string|null $submitMessage
     * @param array|null $metadata
     * @param bool|null $phoneNumberCollection - Whether to collect the customer's phone number.
     * @param array|null $subscriptionData - Additional data for subscription data.
     * @param PaymentMethodCollectionEnum|null $paymentMethodCollection - Payment method collection method.
     * @param array<CustomField>|null $customFields - Custom fields to collect during checkout.
     * @return Session
     * @throws ApiErrorException
     */
    public function createSession(
        ?User                   $user = null,
        string|CheckoutModeEnum $mode = 'setup',
        ?string                 $successUrl = null,
        ?string                 $cancelUrl = null,
        array                   $lineItems = [],
        ?array                  $paymentMethodTypes = null,
        ?string                 $submitMessage = null,
        array                   $metadata = null,
        bool                    $phoneNumberCollection = null,
        array                   $subscriptionData = null,
        PaymentMethodCollectionEnum $paymentMethodCollection = null,
        array                   $customFields = null
    ): Session {
        $customerId = $user ? $this->customersManager->getByUser($user)->id : null;

        if (is_string($mode)) {
            $mode = CheckoutModeEnum::tryFrom($mode);

            if (!$mode) {
                throw new InvalidArgumentException('Invalid checkout mode provided');
            }
        }

        $checkoutOptions = [
            'success_url' => strpos($successUrl, 'http', 0) === 0 ? $successUrl : $this->getSiteUrl() . ($successUrl ?? 'api/v3/payments/stripe/checkout/success'),
            'cancel_url' => strpos($cancelUrl, 'http', 0) === 0 ? $cancelUrl : $this->getSiteUrl() . ($cancelUrl ?? 'api/v3/payments/stripe/checkout/cancel'),
            'mode' => $mode->value,
            'payment_method_types' => $paymentMethodTypes ?? ['card'], // we can possibly add more in the future,
        ];

        if ($customerId) {
            $checkoutOptions['customer'] = $customerId;
        }

        if ($mode === CheckoutModeEnum::SUBSCRIPTION || $mode === CheckoutModeEnum::PAYMENT) {
            $checkoutOptions['line_items'] = $lineItems;
        }

        if ($submitMessage) {
            $checkoutOptions['custom_text']['submit']['message'] = $submitMessage;
        }

        if ($metadata) {
            $checkoutOptions['metadata'] = $metadata;
        }

        if ($phoneNumberCollection) {
            $checkoutOptions['phone_number_collection'] = [
                "enabled" => true
            ];
        }

        if ($customFields) {
            $checkoutOptions['custom_fields'] = array_map(
                fn (CustomField $customField) => $customField->toArray(),
                $customFields
            );
        }

        if ($subscriptionData) {
            $checkoutOptions['subscription_data'] = $subscriptionData;
        }

        if ($paymentMethodCollection) {
            $checkoutOptions['payment_method_collection'] = $paymentMethodCollection->value;
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
