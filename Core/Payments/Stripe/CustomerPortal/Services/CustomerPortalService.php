<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\CustomerPortal\Services;

use Minds\Core\Payments\Stripe\StripeClient;

class CustomerPortalService
{
    public function __construct(
        private readonly StripeClient $stripeClient,
    )
    {
    }

    public function createCustomerPortalSession(): string
    {

    }
}
