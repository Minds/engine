<?php
/**
 * Stripe Payment Intent
 */
namespace Minds\Core\Payments\Stripe\Intents;

use Minds\Traits\MagicAttributes;

/**
 * @method Intent getId(): string
 * @method Intent getCustomerId(): string
 * @method Intent getPaymentMethod(): string
 * @method Intent getStripeAccountId(): string
 * @method Intent getClientSecret(): string
 */
class Intent
{
    use MagicAttributes;

    /** @var string $id */
    protected $id;

    /** @var string $customerId */
    protected $customerId;

    /** @var string $paymentMethod */
    protected $paymentMethod;

    /** @var string $stripeAccountId */
    protected $stripeAccountId;

    /** @var string $clientSecret */
    protected $clientSecret;

    /**
     * Expose to the public apis
     * @param array $extend
     * @return array
     */
    public function export(array $extend = []) : array
    {
        return [
            'id' => $this->id,
            'client_secret' => $this->clientSecret,
        ];
    }
}
