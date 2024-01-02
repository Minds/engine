<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Checkout\Session\Services;

use Minds\Core\Payments\Stripe\StripeClient;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;

class SessionService
{
    public function __construct(
        private readonly StripeClient $stripeClient,
    ) {
    }

    /**
     * @param string $sessionId
     * @return Session
     * @throws ApiErrorException
     */
    public function retrieveCheckoutSession(string $sessionId): Session
    {
        return $this->stripeClient
            ->checkout
            ->sessions
            ->retrieve($sessionId);
    }
}
