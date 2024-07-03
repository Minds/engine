<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Webhooks\Enums;

enum WebhookEventTypeEnum: string
{
    case INVOICE_PAYMENT_SUCCEEDED = 'invoice.payment_succeeded';
    case INVOICE_PAYMENT_FAILED = 'invoice.payment_failed';
    case INVOICE_PAID = 'invoice.paid';
}
