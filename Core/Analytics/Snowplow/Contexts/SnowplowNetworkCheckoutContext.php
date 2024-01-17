<?php
declare(strict_types=1);

namespace Minds\Core\Analytics\Snowplow\Contexts;

use Minds\Core\Payments\Checkout\Enums\CheckoutTimePeriodEnum;

class SnowplowNetworkCheckoutContext implements SnowplowContextInterface
{
    public function __construct(
        public string                 $productId,
        public CheckoutTimePeriodEnum $timePeriod,
        public array                  $addonIds = []
    ) {
    }

    public function getSchema(): string
    {
        return "iglu:com.minds/network_checkout/jsonschema/1-0-1";
    }

    public function getData(): array
    {
        return array_filter([
            'product_id' => $this->productId,
            'time_period' => $this->timePeriod->value,
            'addon_ids' => $this->addonIds,
        ]);
    }
}
