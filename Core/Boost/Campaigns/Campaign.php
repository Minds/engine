<?php
/**
 * Campaign
 * @author edgebal
 */

namespace Minds\Core\Boost\Campaigns;

use JsonSerializable;
use Minds\Entities\Boost\BoostEntityInterface;
use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

/**
 * Class Campaign
 * @package Minds\Core\Boost\Campaigns
 * @method string getUrn()
 * @method Campaign setUrn(string $urn)
 * @method int|string getOwnerGuid()
 * @method Campaign setOwnerGuid(int|string $ownerGuid)
 * @method string getName()
 * @method Campaign setName(string $name)
 * @method string getType()
 * @method Campaign setType(string $type)
 * @method string[] getEntityUrns()
 * @method Campaign setEntityUrns(string[] $entityUrns)
 * @method string[] getHashtags()
 * @method Campaign setHashtags(string[] $hashtags)
 * @method int getStart()
 * @method Campaign setStart(int $start)
 * @method int getEnd()
 * @method Campaign setEnd(int $end)
 * @method string getBudget()
 * @method Campaign setBudget(string $budget)
 * @method string getDeliveryStatus()
 * @method Campaign setDeliveryStatus(string $deliveryStatus)
 * @method int getImpressions()
 * @method Campaign setImpressions(int $impressions)
 * @method int getCpm()
 * @method Campaign setCpm(int $cpm)
 * @method BoostEntityInterface getBoost()
 * @method Campaign setBoost(BoostEntityInterface $boost)
 */
class Campaign implements JsonSerializable
{
    use MagicAttributes;

    /** @var string */
    protected $urn;

    /** @var int|string */
    protected $ownerGuid;

    /** @var string */
    protected $name;

    /** @var string */
    protected $type;

    /** @var string[] */
    protected $entityUrns = [];

    /** @var string[] */
    protected $hashtags;

    /** @var int */
    protected $start;

    /** @var int */
    protected $end;

    /** @var string */
    protected $budget;

    /** @var string */
    protected $deliveryStatus;

    /** @var int */
    protected $impressions;

    /** @var int */
    protected $cpm;

    /** @var BoostEntityInterface */
    protected $boost;

    /**
     * @param User $owner
     * @return Campaign
     */
    public function setOwner(User $owner = null)
    {
        $this->ownerGuid = $owner ? $owner->guid : null;
        return $this;
    }

    /**
     * @return array
     */
    public function export()
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'entity_urns' => $this->entityUrns,
            'hashtags' => $this->hashtags,
            'start' => $this->start,
            'end' => $this->end,
            'budget' => $this->budget,
            'urn' => $this->urn,
            'delivery_status' => $this->deliveryStatus,
            'impressions' => $this->impressions,
            'cpm' => $this->cpm,
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
