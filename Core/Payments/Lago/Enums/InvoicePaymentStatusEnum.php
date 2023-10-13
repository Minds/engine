<?php

namespace Minds\Core\Payments\Lago\Enums;

enum InvoicePaymentStatusEnum: string
{
    case PENDING = "pending";
    case SUCCEEDED = "succeeded";
    case FAILED = "failed";
}
