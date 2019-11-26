<?php

namespace Minds\Core\Boost\Campaigns\Payments;

use JsonSerializable;
use Minds\Traits\MagicAttributes;

/**
 * Class Payment
 * @package Minds\Core\Boost\Campaigns\Payments
 * @method int|string getOwnerGuid()
 * @method Payment setOwnerGuid(int|string $ownerGuid)
 * @method int|string getCampaignGuid()
 * @method Payment setCampaignGuid(int|string $campaignGuid)
 * @method string getTx()
 * @method Payment setTx(string $tx)
 * @method string getSource()
 * @method Payment setSource(string $source)
 * @method double getAmount()
 * @method Payment setAmount(double $amount)
 * @method int getTimeCreated()
 * @method Payment setTimeCreated(int $timeCreated)
 */
class Payment implements JsonSerializable
{
    use MagicAttributes;

    /** @var int|string */
    protected $ownerGuid;

    /** @var int|string */
    protected $campaignGuid;

    /** @var string */
    protected $tx;

    /** @var string */
    protected $source;

    /** @var string */
    protected $amount;

    /** @var int */
    protected $timeCreated;

    /**
     * @return array
     */
    public function export()
    {
        return [
            'owner_guid' => (string) $this->ownerGuid,
            'campaign_guid' => (string) $this->campaignGuid,
            'tx' => $this->tx,
            'source' => $this->source,
            'amount' => $this->amount,
            'time_created' => $this->timeCreated,
        ];
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->export();
    }
}
