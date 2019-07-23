<?php
/**
 * Stripe Checkout Session
 */
namespace Minds\Core\Payments\Stripe\Checkout;

use Minds\Traits\MagicAttributes;

class CheckoutSession
{
    use MagicAttributes;

    /** @var string $userGuid */
    private $sessionId;

    /**
     * Expose to the public apis
     * @param array $extend
     * @return array
     */
    public function export($extend = [])
    {
        return [
            'sessionId' => (string) $this->sessionId,
        ];
    }

}
