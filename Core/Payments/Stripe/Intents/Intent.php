<?php
/**
 * Stripe Payment Intent
 */
namespace Minds\Core\Payments\Stripe\Intents;

use Minds\Traits\MagicAttributes;

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
    public function export($extend = [])
    {
        return [
            'id' => $this->id,
            'client_secret' => $this->clientSecret,
        ];
    }

}
