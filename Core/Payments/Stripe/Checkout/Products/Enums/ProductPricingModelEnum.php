<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Checkout\Products\Enums;

enum ProductPricingModelEnum: string
{
    case RECURRING = 'recurring';
    case ONE_TIME = 'one_time';
}
