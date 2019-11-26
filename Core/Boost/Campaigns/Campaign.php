<?php

namespace Minds\Core\Boost\Campaigns;

use Exception;
use JsonSerializable;
use Minds\Common\Urn;
use Minds\Core\Boost\Campaigns\Payments\Payment;
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
 * @method int[] getNsfw()
 * @method int getStart()
 * @method Campaign setStart(int $start)
 * @method int getEnd()
 * @method Campaign setEnd(int $end)
 * @method string getBudget()
 * @method Campaign setBudget(string $budget)
 * @method string getBudgetType()
 * @method Campaign setBudgetType(string $budgetType)
 * @method Payment[] getPayments()
 * @method Campaign setPayments(Payment[] $payments)
 * @method string getChecksum()
 * @method Campaign setChecksum(string $checksum)
 * @method int getImpressions()
 * @method Campaign setImpressions(int $impressions)
 * @method int getImpressionsMet()
 * @method Campaign setImpressionsMet(int $impressionsMet)
 * @method int getRating()
 * @method Campaign setRating(int $value)
 * @method int getQuality()
 * @method Campaign setQuality(int $value)
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
    const STATUS_PENDING = 'pending';

    /** @var string */
    const STATUS_CREATED = 'created';

    /** @var string */
    const STATUS_APPROVED = 'approved';

    /** @var string */
    const STATUS_REJECTED = 'rejected';

    /** @var string */
    const STATUS_REVOKED = 'revoked';

    /** @var string */
    const STATUS_COMPLETED = 'completed';

    /** @var int  */
    const RATING_SAFE = 1;
    /** @var int  */
    const RATING_OPEN = 2;

    /** @var string */
    protected $urn;

    /** @var string */
    protected $type;

    /** @var int|string */
    protected $ownerGuid;

    /** @var string */
    protected $name;

    /** @var string[] */
    protected $entityUrns = [];

    /** @var string[] */
    protected $hashtags;

    /** @var int[] */
    protected $nsfw;

    /** @var int */
    protected $start;

    /** @var int */
    protected $end;

    /** @var string */
    protected $budget;

    /** @var string */
    protected $budgetType;

    /** @var array */
    protected $payments = [];

    /** @var string */
    protected $checksum;

    /** @var int */
    protected $impressions;

    /** @var int */
    protected $impressionsMet;

    /** @var int */
    protected $rating;

    /** @var int */
    protected $quality;

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


    public function getGuid(): string
    {
        if (!$this->urn) {
            return '';
        }

        try {
            return (new Urn($this->urn))->getNss();
        } catch (Exception $exception) {
            return '';
        }
    }

    /**
     * @param User $owner
     * @return Campaign
     */
    public function setOwner(User $owner = null): self
    {
        $this->ownerGuid = $owner ? $owner->guid : null;
        return $this;
    }

    /**
     * @param Payment $payment
     * @return Campaign
     */
    public function pushPayment(Payment $payment): self
    {
        $this->payments[] = $payment;
        return $this;
    }

    public function getDeliveryStatus(): string
    {
        if ($this->completedTimestamp) {
            return static::STATUS_COMPLETED;
        } elseif ($this->rejectedTimestamp) {
            return static::STATUS_REJECTED;
        } elseif ($this->revokedTimestamp) {
            return static::STATUS_REVOKED;
        } elseif ($this->reviewedTimestamp) {
            return static::STATUS_APPROVED;
        } elseif ($this->createdTimestamp) {
            return static::STATUS_CREATED;
        }

        return static::STATUS_PENDING;
    }

    /**
     * @param int[] $value
     * @return Campaign
     */
    public function setNsfw($value): self
    {
        $this->nsfw = $value;
        $this->setRating(count($this->getNsfw()) > 0 ? static::RATING_OPEN : static::RATING_SAFE); // 2 = open; 1 = safe

        return $this;
    }

    public function cpm(): float
    {
        if (!$this->impressions || $this->impressions === 0) {
            return 0;
        }

        return ($this->budget / $this->impressions) * 1000;
    }

    public function isDelivering(): bool
    {
        return $this->getDeliveryStatus() === static::STATUS_APPROVED;
    }

    public function shouldBeStarted(int $now): bool
    {
        $isCreated = $this->getDeliveryStatus() === static::STATUS_CREATED;
        $started = $now >= $this->getStart() && $now < $this->getEnd();

        return $isCreated && $started;
    }

    public function shouldBeCompleted(int $now): bool
    {
        $isDelivering = $this->isDelivering();
        $ended = $now >= $this->getEnd();
        $fulfilled = $this->getImpressionsMet() >= $this->getImpressions();

        return $isDelivering && ($ended || $fulfilled);
    }

    public function hasStarted(): bool
    {
        return !in_array($this->getDeliveryStatus(), [static::STATUS_PENDING, static::STATUS_CREATED], true);
    }

    public function hasFinished(): bool
    {
        return in_array($this->getDeliveryStatus(), [
            static::STATUS_COMPLETED,
            static::STATUS_REJECTED,
            static::STATUS_REVOKED,
        ], false);
    }

    public function export(bool $isGetData = false): array
    {
        $data = [
            'urn' => $this->urn,
            'name' => $this->name,
            'entity_urns' => $this->entityUrns,
            'hashtags' => $this->hashtags,
            'nsfw' => $this->nsfw,
            'start' => $this->start,
            'end' => $this->end,
            'budget' => $this->budget,
            'budget_type' => $this->budgetType,
            'checksum' => $this->checksum,
            'impressions' => $this->impressions,
            'impressions_met' => $this->impressionsMet,
            'created_timestamp' => $this->createdTimestamp,
            'reviewed_timestamp' => $this->reviewedTimestamp,
            'revoked_timestamp' => $this->revokedTimestamp,
            'rejected_timestamp' => $this->rejectedTimestamp,
            'completed_timestamp' => $this->completedTimestamp,
        ];

        if ($isGetData) {
            $data['owner_guid'] = (string)$this->ownerGuid;
            $data['rating'] = $this->rating;
            $data['quality'] = $this->quality;
        } else {
            $data['type'] = $this->type;
            $data['payments'] = $this->payments;
            $data['delivery_status'] = $this->getDeliveryStatus();
            $data['cpm'] = $this->cpm();
        }

        return $data;
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

    public function getData(): array
    {
        return $this->export(true);
    }
}
