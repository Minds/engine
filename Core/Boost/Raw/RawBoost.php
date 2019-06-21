<?php
/**
 * RawBoost
 * @author edgebal
 */

namespace Minds\Core\Boost\Raw;

use Minds\Traits\MagicAttributes;

/**
 * Class RawBoost
 * @package Minds\Core\Boost\Raw
 * @method int|string getGuid()
 * @method RawBoost setGuid(int|string $guid)
 * @method int|string getOwnerGuid()
 * @method RawBoost setOwnerGuid(int|string $ownerGuid)
 * @method string getType()
 * @method RawBoost setType(string $type)
 * @method int|string getEntityGuid()
 * @method RawBoost setEntityGuid(int|string $entityGuid)
 * @method string[] getEntityUrns()
 * @method RawBoost setEntityUrns(string[] $entityUrns)
 * @method string getBid()
 * @method RawBoost setBid(string $bid)
 * @method string getBidType()
 * @method RawBoost setBidType(string $bidType)
 * @method string getTokenMethod()
 * @method RawBoost setTokenMethod(string $tokenMethod)
 * @method bool isPriority()
 * @method RawBoost setPriority(bool $priority)
 * @method int getRating()
 * @method RawBoost setRating(int $rating)
 * @method string[] getTags()
 * @method RawBoost setTags(string[] $tags)
 * @method string[] getNsfw()
 * @method RawBoost setNsfw(string[] $nsfw)
 * @method int getImpressions()
 * @method RawBoost setImpressions(int $impressions)
 * @method int getImpressionsMet()
 * @method RawBoost setImpressionsMet(int $impressionsMet)
 * @method string getTransactionId()
 * @method RawBoost setTransactionId(string $transactionId)
 * @method string getChecksum()
 * @method RawBoost setChecksum(string $checksum)
 * @method int getRejectionReason()
 * @method RawBoost setRejectionReason(int $rejectionReason)
 * @method bool isCampaign()
 * @method RawBoost setCampaign(bool $campaign)
 * @method string getCampaignName()
 * @method RawBoost setCampaignName(string $campaignName)
 * @method int getCampaignStart()
 * @method RawBoost setCampaignStart(int $campaignStart)
 * @method int getCampaignEnd()
 * @method RawBoost setCampaignEnd(int $campaignEnd)
 * @method int getCreatedTimestamp()
 * @method RawBoost setCreatedTimestamp(int $createdTimestamp)
 * @method int getReviewedTimestamp()
 * @method RawBoost setReviewedTimestamp(int $reviewedTimestamp)
 * @method int getRevokedTimestamp()
 * @method RawBoost setRevokedTimestamp(int $revokedTimestamp)
 * @method int getRejectedTimestamp()
 * @method RawBoost setRejectedTimestamp(int $rejectedTimestamp)
 * @method int getCompletedTimestamp()
 * @method RawBoost setCompletedTimestamp(int $completedTimestamp)
 * @method mixed getMongoId()
 * @method RawBoost setMongoId(mixed $deprecatedValue)
 * @method mixed getEntity()
 * @method RawBoost setEntity(mixed $deprecatedValue)
 * @method mixed getOwner()
 * @method RawBoost setOwner(mixed $deprecatedValue)
 * @method mixed getState()
 * @method RawBoost setState(mixed $deprecatedValue)
 */
class RawBoost
{
    use MagicAttributes;

    /** @var int|string */
    protected $guid;

    /** @var int|string */
    protected $ownerGuid;

    /** @var string */
    protected $type;

    /** @var int|string */
    protected $entityGuid;

    /** @var string */
    protected $entityUrns;

    /** @var string */
    protected $bid;

    /** @var string */
    protected $bidType;

    /** @var string */
    protected $tokenMethod;

    /** @var bool */
    protected $priority;

    /** @var int */
    protected $rating;

    /** @var string[] */
    protected $tags = [];

    /** @var int[] */
    protected $nsfw = [];

    /** @var int */
    protected $impressions;

    /** @var int */
    protected $impressionsMet;

    /** @var string */
    protected $transactionId;

    /** @var string */
    protected $checksum;

    /** @var int */
    protected $rejectionReason;

    /** @var string */
    protected $campaignName;

    /** @var int */
    protected $campaignStart;

    /** @var int */
    protected $campaignEnd;

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

    // Legacy below. Do not use.

    /** @var mixed */
    protected $mongoId;

    /** @var mixed */
    protected $entity;

    /** @var mixed */
    protected $owner;

    /** @var mixed */
    protected $state;
}
