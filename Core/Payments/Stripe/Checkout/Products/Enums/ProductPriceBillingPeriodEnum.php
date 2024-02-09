<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Checkout\Products\Enums;

enum ProductPriceBillingPeriodEnum: string
{
    case MONTHLY = 'month';
    case YEARLY = 'year';
}
