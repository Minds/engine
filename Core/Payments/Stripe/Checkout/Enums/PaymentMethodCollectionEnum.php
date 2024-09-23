<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Checkout\Enums;

enum PaymentMethodCollectionEnum: string
{
    case ALWAYS = 'always';
    case IF_REQUIRED = 'if_required';
}
