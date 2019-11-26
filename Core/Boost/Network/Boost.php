<?php
/**
 * Boost entity
 */
namespace Minds\Core\Boost\Network;

use Minds\Entities\Entity;
use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

/**
 * @method Boost setGuid(int $guid)
 * @method int getGuid()
 * @method Boost setEntiyGuid(int $entityGuid)
 * @method int getEntityGuid()
 * @method Boost setEntity($entity)
 * @method Entity getEntity()
 * @method Boost setBid(double $bid)
 * @method double getBid()
 * @method Boost setBidType(string $bidType)
 * @method string getBidType()
 * @method Boost setImpressions(int $impressions)
 * @method int getImpressions()
 * @method Boost setImpressionsMet(int $impressions)
 * @method int getImpressionsMet()
 * @method Boost setOwnerGuid(int $ownerGuid)
 * @method int getOwnerGuid()
 * @method Boost setOwner(User $owner)
 * @method User getOwner()
 * @method int getCreatedTimestamp()
 * @method Boost setCreatedTimestamp(int $ts)
 * @method int getReviewedTimestamp()
 * @method Boost setReviewedTimestamp(int $ts)
 * @method int getRejectedTimestamp()
 * @method Boost setRejectedTimestamp(int $ts)
 * @method int getRevokedTimestamp()
 * @method Boost setRevokedTimestamp(int $ts)
 * @method int getCompletedTimestamp()
 * @method Boost setCompletedTimestamp(int $ts)
 * @method string getTransactionId()
 * @method Boost setTransactionId(string $transactionId)
 * @method string getType()
 * @method Boost setType(string $value)
 * @method bool getPriority()
 * @method Boost setPriority(bool $priority)
 * @method int getRating()
 * @method Boost setRating(int $rating)
 * @method array getTags()
 * @method Boost setTags(array $value)
 * @method array getNsfw()
 * @method Boost setNsfw(array $nsfw)
 * @method int getRejectedReason()
 * @method Boost setRejectedReason(int $reason)
 * @method string getChecksum()
 * @method Boost setChecksum(string $checksum)
 */
class Boost
{
    use MagicAttributes;

    const STATE_COMPLETED = 'completed';
    const STATE_REJECTED = 'rejected';
    const STATE_APPROVED = 'approved';
    const STATE_REVOKED = 'revoked';
    const STATE_CREATED = 'created';

    const TYPE_NEWSFEED = 'newsfeed';
    const TYPE_CONTENT = 'content';

    const RATING_SAFE = 1;
    const RATING_OPEN = 2;

    /** @var int $guid */
    private $guid;

    /** @var int $entityGuid */
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

    /** @var int $ownerGuid */
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

    /**
     * Return the state
     */
    public function getState()
    {
        if ($this->completedTimestamp) {
            return self::STATE_COMPLETED;
        }
        if ($this->rejectedTimestamp) {
            return self::STATE_REJECTED;
        }
        if ($this->reviewedTimestamp) {
            return self::STATE_APPROVED;
        }
        if ($this->revokedTimestamp) {
            return self::STATE_REVOKED;
        }
        return self::STATE_CREATED;
    }

    /**
     * Return if the boost is an onchain boost
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

    /* TODO - Spec Test this */
    /**
     * Validate the boost type string
     * @param string $type
     * @return bool
     */
    public static function validType(string $type): bool
    {
        $validTypes = [
            self::TYPE_CONTENT,
            self::TYPE_NEWSFEED
        ];

        return in_array($type, $validTypes, true);
    }

    /**
     * Returns true if Boost has an entity object set
     * @return bool
     */
    public function hasEntity(): bool
    {
        return !is_null($this->entity);
    }
}
