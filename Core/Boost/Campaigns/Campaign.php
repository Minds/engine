<?php
/**
 * Campaign
 * @author edgebal
 */

namespace Minds\Core\Boost\Campaigns;

use JsonSerializable;
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
 * @method int getImpressions()
 * @method Campaign setImpressions(int $impressions)
 * @method int getImpressionsMet()
 * @method Campaign setImpressionsMet(int $impressionsMet)
 * @method int getCreatedTimestamp()
 * @method Campaign setCreatedTimestamp(int $createdTimestamp)
 * @method int getReviewedTimestamp()
 * @method Campaign setReviewedTimestamp(int $reviewedTimestamp)
 * @method int getRevokedTimestamp()
 * @method Campaign setRevokedTimestamp(int $revokedTimestamp)
 * @method int getRejectedTimestamp()
 * @method Campaign setRejectedTimestamp(int $rejectedTimestamp)
 * @method int getCompletedTimestamp()
 * @method Campaign setCompletedTimestamp(int $completedTimestamp)
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

    /** @var int */
    protected $impressions;

    /** @var int */
    protected $impressionsMet;

    /** @var int */
    protected $createdTimestamp;

    /** @var int */
    protected $reviewedTimestamp;

    /** @var int */
    protected $revokedTimestamp;

    /** @var int */
    protected $rejectedTimestamp;

    /** @var int */
    protected $completedTimestamp;

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
     * @return string
     */
    public function getDeliveryStatus()
    {
        if ($this->completedTimestamp) {
            return 'completed';
        } elseif ($this->rejectedTimestamp) {
            return 'rejected';
        } elseif ($this->revokedTimestamp) {
            return 'revoked';
        } elseif ($this->reviewedTimestamp) {
            return 'approved';
        } elseif ($this->createdTimestamp) {
            return 'created';
        }

        return 'pending';
    }

    /**
     * @return float
     */
    public function cpm()
    {
        if (!$this->impressions || $this->impressions === 0) {
            return 0;
        }

        return ($this->budget / $this->impressions) * 1000;
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
            'impressions' => $this->impressions,
            'impressions_met' => $this->impressionsMet,
            'created_timestamp' => $this->createdTimestamp,
            'reviewed_timestamp' => $this->reviewedTimestamp,
            'revoked_timestamp' => $this->revokedTimestamp,
            'rejected_timestamp' => $this->rejectedTimestamp,
            'completed_timestamp' => $this->completedTimestamp,
            'delivery_status' => $this->getDeliveryStatus(),
            'cpm' => $this->cpm(),
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
