<?php

namespace Minds\Core\Payments\Lago\Enums;

enum PaymentProviderEnum: string
{
    case STRIPE = 'stripe';
    case ADYEN = 'adyen';
    case GOCARDLESS = 'gocardless';
}
