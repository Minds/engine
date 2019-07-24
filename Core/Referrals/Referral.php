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
 * @method Referral setProspect()
 * @method User getProspect()
 * @method Referral setRegisterTimestamp(int $ts)
 * @method int getRegisterTimestamp
 * @method Referral setJoinTimestamp(int $ts)
 * @method int getJoinTimestamp
 * @method Referral setPingTimestamp(int $ts)
 * @method int getPingTimestamp

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

    /** @var int $pingTimestamp */
    private $pingTimestamp;

    /**
     * Return the state
     * @return string
     */
    public function getState()
    {
        // Referral goes from pending to complete when the prospect joins rewards
        if ($this->joinTimestamp) {
            return 'complete';
        }
        return 'pending';
    }

    /**
     * Return whether 7 days has passed since last ping
     * @return bool
     */
    public function getPingable()
    {
        // Disable ping if prospect has already joined rewards
        if ($this->joinTimestamp) {
            return false;
        }

        // Duration referrer must wait before re-pinging (in seconds)
        $waitTime = 60*60*24*7; // 7 days

        $now = time();
        $elapsedTime = $now - $this->pingTimestamp;

        // Not enough time has elapsed
        if ($this->pingTimestamp && $elapsedTime < $waitTime) {
            return false;
        }

        return true;
    }

    /**
     * Return the URN of this referral
     * @return string
     */
    public function getUrn()
    {
        $parts = [
            $this->getReferrerGuid(),
            $this->getProspectGuid(),
        ];
        return "urn:referral:" . implode('-', $parts);
    }


    /**
     * Export
     * @return array
     */
    public function export()
    {
        return [
            'referrer_guid' => $this->referrerGuid,
            'prospect' => $this->prospect ? $this->prospect->export() : null,
            'state' => $this->getState(),
            'pingable' => $this->getPingable(),
            'register_timestamp' => $this->registerTimestamp * 1000,
            'join_timestamp' => $this->joinTimestamp * 1000,
            'ping_timestamp' => $this->pingTimestamp * 1000,
            'urn' => $this->getUrn(),
        ];
    }
}
