<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\CustomerPortal\Helpers;

use InvalidArgumentException;
use Minds\Core\Payments\Stripe\CustomerPortal\Enums\CustomerPortalFlowTypeEnum;

class CustomerPortalFlowConfiguration
{
    public function __construct(
        private readonly CustomerPortalFlowTypeEnum $flowType,
        private readonly ?string                    $redirectUri = null,
        private readonly ?string                    $subscriptionId = null,
    ) {
        if ($this->flowType !== CustomerPortalFlowTypeEnum::PAYMENT_METHOD_UPDATE && !$this->subscriptionId) {
            throw new InvalidArgumentException('Subscription ID is required for flow type: ' . $this->flowType->value);
        }
    }

    public function toArray(): array
    {
        $flowData = [
            'type' => $this->flowType->value,
        ];

        if ($this->flowType === CustomerPortalFlowTypeEnum::SUBSCRIPTION_CANCEL) {
            $flowData['after_completion']['type'] = 'redirect';
            $flowData['after_completion']['redirect']['return_url'] = $this->redirectUri;
            $flowData['subscription_cancel']['subscription'] = $this->subscriptionId;
        }

        return $flowData;
    }
}
