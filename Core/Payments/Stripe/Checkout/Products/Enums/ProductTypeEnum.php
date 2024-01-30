<?php

namespace Minds\Core\Payments\Stripe\Checkout\Products\Enums;

enum ProductTypeEnum: string
{
    case NETWORK = 'networks';
    case SITE_MEMBERSHIP = 'site_memberships';
}
