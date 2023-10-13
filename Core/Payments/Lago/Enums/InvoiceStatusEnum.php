<?php

namespace Minds\Core\Payments\Lago\Enums;

enum InvoiceStatusEnum: string
{
    case DRAFT = "draft";
    case FINALIZED = "finalized";
}
