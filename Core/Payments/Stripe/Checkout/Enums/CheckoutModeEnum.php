<?php

namespace Minds\Core\Payments\Stripe\Checkout\Enums;

enum CheckoutModeEnum: string
{
    case SETUP = 'setup';
    case PAYMENT = 'payment';
    case SUBSCRIPTION = 'subscription';
}
