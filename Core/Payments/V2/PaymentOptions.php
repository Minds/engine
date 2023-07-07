<?php
declare(strict_types=1);

namespace Minds\Core\Payments\V2;

use Minds\Traits\MagicAttributes;

/**
 * @method self setUserGuid(int|null $userGuid)
 * @method int|null getUserGuid()
 * @method self setWithAffiliate(bool $withAffiliate)
 * @method bool getWithAffiliate()
 * @method self setAffiliateGuid(int|null $affiliateGuid)
 * @method int|null getAffiliateGuid()
 * @method self setFromTimestamp(int $fromTimestamp)
 * @method int getFromTimestamp()
 * @method self setToTimestamp(int|null $toTimestamp)
 * @method int|null getToTimestamp()
 * @method self setPaymentTypes(array $paymentTypes)
 * @method array getPaymentTypes()
 * @method self setPaymentMethod(int|null $paymentMethod)
 * @method int|null getPaymentMethod()
 * @method self setPaymentStatus(int|null $paymentStatus)
 * @method int|null getPaymentStatus()
 */
class PaymentOptions
{
    use MagicAttributes;
    private ?int $userGuid = null;
    private bool $withAffiliate = false;
    private ?int $affiliateGuid = null;

    private int $fromTimestamp;
    private ?int $toTimestamp = null;
    private array $paymentTypes = [];
    private ?int $paymentMethod = null;
    private ?int $paymentStatus = null;
}
