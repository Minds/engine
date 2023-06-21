<?php
declare(strict_types=1);

namespace Minds\Core\Payments\V2\Enums;

class PaymentType
{
    public const BOOST_PAYMENT = 1;
    public const MINDS_PLUS_PAYMENT = 2;
    public const MINDS_PRO_PAYMENT = 3;
    public const WIRE_PAYMENT = 4;
    public const GIFT_CARD_PURCHASE = 5;
}
