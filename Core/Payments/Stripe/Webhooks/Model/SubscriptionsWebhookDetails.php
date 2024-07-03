<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Webhooks\Model;

class SubscriptionsWebhookDetails
{
    public function __construct(
        public readonly ?string $stripeWebhookId = null,
        public readonly ?string $stripeWebhookSecret = null
    ) {
    }
}
