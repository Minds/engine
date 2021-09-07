<?php
/**
 * Boost entity
 */
namespace Minds\Core\Boost\Network;

use Minds\Common\Access;
use Minds\Entities\EntityInterface;
use Minds\Traits\MagicAttributes;

/**
 * Boost Entity
 * @package Minds\Core\Boost\Network
 * @method Boost setGuid(long $guid)
 * @method long getGuid()
 * @method string getEntityGuid()
 * @method Boost setEntityGuid(string $guid)
 * @method Boost setEntity()
 * @method Entity getEntity()
 * @method Boost setBid()
 * @method Boost setBidType()
 * @method Booot setImpressions()
 * @method int getImpressions()
 * @method Boost setOwnerGuid()
 * @method long getOwnerGuid()
 * @method Boost setOwner()
 * @method User getOwner()
 *
 * @method Boost setRejectedReason(int $reason)
 * @method int getRejectedReason()
 * @method Boost setCompletedTimestamp(int $ts)
 * @method Boost setReviewedTimestamp(int $ts)
 * @method Boost setRejectedTimestamp(int $ts)
 * @method Boost setCreatedTimestamp(int $ts)
 * @method Boost setRevokedTimestamp(int $ts)
 * @method array getTags()
 * @method Boost setTags(array $value)
 * @method string getType()
 * @method string getTransactionId()
 */
class Boost implements EntityInterface
{
    use MagicAttributes;

    /** @var long $guid */
    private $guid;

    /** @var long $entityGuid */
    private $entityGuid;

    /** @var Entity $entity */
    private $entity;

    /** @var double $bid */
    private $bid;

    /** @var string $bidType */
    private $bidType;

    /** @var int $impressions */
    private $impressions;

    /** @var int $impressionsMet */
    private $impressionsMet;

    /** @var long $ownerGuid */
    private $ownerGuid;

    /** @var User $owner */
    private $owner;

    /** @var int $createdTimestamp */
    private $createdTimestamp;

    /** @var int $reviewedTimestamp */
    private $reviewedTimestamp;

    /** @var int $rejectedTimestamp */
    private $rejectedTimestamp;

    /** @var int $revokedTimestamp */
    private $revokedTimestamp;

    /** @var int $completedTimestamp */
    private $completedTimestamp;

    /** @var string $transactionId */
    private $transactionId;

    /** @var string $type */
    private $type = 'newsfeed';

    /** @var bool $priority */
    private $priority = false;

    /** @var int $rating */
    private $rating;

    /** @var array $tags */
    private $tags = [];

    /** @var array $nsfw */
    private $nsfw = [];

    /** @var int $rejectedReason */
    private $rejectedReason = -1;

    /** @var string $checksum */
    private $checksum;

    /** @var string $mongoId */
    private $mongoId;

    /**
     * Return the state
     */
    public function getState()
    {
        if ($this->completedTimestamp) {
            return 'completed';
        }
        if ($this->rejectedTimestamp) {
            return 'rejected';
        }
        if ($this->reviewedTimestamp) {
            return 'approved';
        }
        if ($this->revokedTimestamp) {
            return 'revoked';
        }
        return 'created';
    }

    /**
     * @return string
     */
    public function getGuid(): string
    {
        return (string) $this->guid;
    }

    /**
     * @return string
     */
    public function getOwnerGuid(): string
    {
        return (string) $this->ownerGuid;
    }

    /**
     * @return string
     */
    public function getUrn(): string
    {
        return "urn:boost:{$this->getType()}:{$this->getGuid()}";
    }

    /**
     * TODO: Fix this, type should be boost not boost type
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Boosts treat type different
     * @return null
     */
    public function getSubtype(): ?string
    {
        return null;
    }

    /**
     * @return string
     */
    public function getAccessId(): string
    {
        return Access::idToString(Access::PUBLIC);
    }

    /**
     * Return if the boost is an onchain boost
     * Do not rely on this function if the TXID is not set.
     * @return boolean
     */
    public function isOnChain()
    {
        return (strpos($this->getTransactionId(), '0x', 0) === 0);
    }

    /**
     * Export
     * @param array $fields
     * @return array
     */
    public function export($fields = [])
    {
        return [
            'guid' => (string) $this->guid,
            'owner_guid' => (string) $this->ownerGuid,
            'owner' => $this->owner ? $this->owner->export() : null,
            'entity_guid' => (string) $this->entityGuid,
            'entity' => $this->entity ? $this->entity->export() : null,
            'bid' => $this->bid,
            'bid_type' => $this->bidType,
            'impressions' => $this->impressions,
            '@created' => $this->createdTimestamp,
            '@reviewed' => $this->reviewedTimestamp,
            '@rejected' => $this->rejectedTimestamp,
            '@revoked' => $this->revokedTimestamp,
            '@completed' => $this->completedTimestamp,
            'priority' => (bool) $this->priority,
            'rating' => (int) $this->rating,
            'tags' => $this->tags,
            'nsfw' => $this->nsfw,
            'checksum' => $this->checksum,
            'state' => $this->getState(),
            'transaction_id' => $this->transactionId,
        ];
    }
}
