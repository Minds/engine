<?php

namespace Minds\Core\Payments\Stripe\Checkout\Enums;

enum CaptureMethodEnum: string
{
    case AUTOMATIC_ASYNC = 'automatic_async';
    case MANUAL = 'manual';
}
