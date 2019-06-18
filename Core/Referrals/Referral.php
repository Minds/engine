<?php
/**
 * Referral Model
 */
namespace Minds\Core\Referrals;

use Minds\Traits\MagicAttributes;

/**
 * Referral
 * @method Referral setReferrerGuid()
 * @method long getReferrerGuid()
 * @method Referral setProspectGuid()
 * @method long getProspectGuid()
 * @method Referral setProspect()
 * @method User getProspect()
 * @method Referral setRegisterTimestamp(int $ts)
 * @method int getRegisterTimestamp
 * @method Referral setJoinTimestamp(int $ts)
 * @method int getJoinTimestamp

 */
class Referral
{
    use MagicAttributes;

    /** @var long $referrerGuid */
    private $referrerGuid;

    /** @var long $prospectGuid */
    private $prospectGuid;

    /** @var User $prospect */
    private $prospect;

    /** @var int $registerTimestamp */
    private $registerTimestamp;

    /** @var int $joinTimestamp */
    private $joinTimestamp;

}
