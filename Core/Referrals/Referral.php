<?php
/**
 * Referral Model
 */
namespace Minds\Core\Referrals;

use Minds\Traits\MagicAttributes;

/**
 * Referral
 * @package Minds\Core\Referrals
 * @method Referral setReferrerGuid()
 * @method long getReferrerGuid()
 * @method Referral setProspectGuid()
 * @method long getProspectGuid()
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

    /**
     * Return the state
     * @return string
     */
    public function getState()
    {
        if ($this->joinTimestamp) {
            return 'complete';
        }
        return 'pending';
    }

    /**
     * Export
     * @return array
     */
    public function export()
    {
        return [
            'referrer_guid' => $this->referrerGuid,
            // 'prospect_guid' => $this->prospectGuid,
            'prospect' => $this->prospect ? $this->prospect->export() : null,
            'state' => $this->getState(),
            'register_timestamp' => $this->registerTimestamp,
            'join_timestamp' => $this->joinTimestamp,
        ];
    }
}
