<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\CustomerPortal\Enums;

enum CustomerPortalFlowTypeEnum: string
{
    case PAYMENT_METHOD_UPDATE = 'payment_method_update';
    case SUBSCRIPTION_CANCEL = 'subscription_cancel';
    case SUBSCRIPTION_UPDATE = 'subscription_update';
    case SUBSCRIPTION_UPDATE_CONFIRM = 'subscription_update_confirm';
}
