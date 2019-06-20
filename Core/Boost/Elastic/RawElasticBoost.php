<?php
/**
 * RawElasticBoost
 * @author edgebal
 */

namespace Minds\Core\Boost\Elastic;

use Minds\Traits\MagicAttributes;

/**
 * Class RawElasticBoost
 * @package Minds\Core\Boost\Elastic
 * @method int|string getGuid()
 * @method RawElasticBoost setGuid(int|string $guid)
 * @method int|string getOwnerGuid()
 * @method RawElasticBoost setOwnerGuid(int|string $ownerGuid)
 * @method string getType()
 * @method RawElasticBoost setType(string $type)
 * @method int|string getEntityGuid()
 * @method RawElasticBoost setEntityGuid(int|string $entityGuid)
 * @method string[] getEntityUrns()
 * @method RawElasticBoost setEntityUrns(string[] $entityUrns)
 * @method string getBid()
 * @method RawElasticBoost setBid(string $bid)
 * @method string getBidType()
 * @method RawElasticBoost setBidType(string $bidType)
 * @method string getTokenMethod()
 * @method RawElasticBoost setTokenMethod(string $tokenMethod)
 * @method bool isPriority()
 * @method RawElasticBoost setPriority(bool $priority)
 * @method int getRating()
 * @method RawElasticBoost setRating(int $rating)
 * @method string[] getTags()
 * @method RawElasticBoost setTags(string[] $tags)
 * @method int getImpressions()
 * @method RawElasticBoost setImpressions(int $impressions)
 * @method int getImpressionsMet()
 * @method RawElasticBoost setImpressionsMet(int $impressionsMet)
 * @method bool isCampaign()
 * @method RawElasticBoost setCampaign(bool $campaign)
 * @method string getCampaignName()
 * @method RawElasticBoost setCampaignName(string $campaignName)
 * @method int getCampaignStart()
 * @method RawElasticBoost setCampaignStart(int $campaignStart)
 * @method int getCampaignEnd()
 * @method RawElasticBoost setCampaignEnd(int $campaignEnd)
 * @method int getCreatedTimestamp()
 * @method RawElasticBoost setCreatedTimestamp(int $createdTimestamp)
 * @method int getReviewedTimestamp()
 * @method RawElasticBoost setReviewedTimestamp(int $reviewedTimestamp)
 * @method int getRevokedTimestamp()
 * @method RawElasticBoost setRevokedTimestamp(int $revokedTimestamp)
 * @method int getRejectedTimestamp()
 * @method RawElasticBoost setRejectedTimestamp(int $rejectedTimestamp)
 * @method int getCompletedTimestamp()
 * @method RawElasticBoost setCompletedTimestamp(int $completedTimestamp)
 */
class RawElasticBoost
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

    /** @var int */
    protected $impressions;

    /** @var int */
    protected $impressionsMet;

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
}
