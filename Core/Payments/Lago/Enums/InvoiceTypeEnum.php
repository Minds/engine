<?php

namespace Minds\Core\Payments\Lago\Enums;

enum InvoiceTypeEnum: string
{
    case SUBSCRIPTION = "subscription";
    case ONE_OFF = "one_off";
    case ADD_ON = "add_on";
    case CREDIT = "credit";
}
