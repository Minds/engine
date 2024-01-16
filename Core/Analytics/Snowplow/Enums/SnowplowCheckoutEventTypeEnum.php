<?php

namespace Minds\Core\Analytics\Snowplow\Enums;

enum SnowplowCheckoutEventTypeEnum: int
{
    case CHECKOUT_STARTED = 1;
    case CHECKOUT_PAYMENT = 2;
    case CHECKOUT_COMPLETED = 3;
}
