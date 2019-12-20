<?php

namespace Minds\Core\Boost\Network;

use Minds\Common\Urn;
use Minds\Entities\Entity;
use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

/**
 * Class Campaign
 * @package Minds\Core\Boost\Network
 * @method Campaign setGuid(int $guid)
 * @method int getGuid()
 * @method Campaign setEntityGuid(int $entityGuid)
 * @method int getEntityGuid()
 * @method Campaign setEntity($entity)
 * @method Entity getEntity()
 * @method Campaign setBid(double $bid)
 * @method double getBid()
 * @method Campaign setBidType(string $bidType)
 * @method string getBidType()
 * @method Campaign setImpressions(int $impressions)
 * @method int getImpressions()
 * @method Campaign setImpressionsMet(int $impressions)
 * @method int getImpressionsMet()
 * @method Campaign setOwnerGuid(int $ownerGuid)
 * @method int getOwnerGuid()
 * @method Campaign setOwner(User $owner)
 * @method User getOwner()
 * @method int getCreatedTimestamp()
 * @method Campaign setCreatedTimestamp(int $ts)
 * @method int getReviewedTimestamp()
 * @method Campaign setReviewedTimestamp(int $ts)
 * @method int getRejectedTimestamp()
 * @method Campaign setRejectedTimestamp(int $ts)
 * @method int getRevokedTimestamp()
 * @method Campaign setRevokedTimestamp(int $ts)
 * @method int getCompletedTimestamp()
 * @method Campaign setCompletedTimestamp(int $ts)
 * @method string getTransactionId()
 * @method Campaign setTransactionId(string $transactionId)
 * @method string getType()
 * @method Campaign setType(string $value)
 * @method bool getPriority()
 * @method Campaign setPriority(bool $priority)
 * @method int getRating()
 * @method Campaign setRating(int $rating)
 * @method array getTags()
 * @method Campaign setTags(array $value)
 * @method array getNsfw()
 * @method Campaign setNsfw(array $nsfw)
 * @method int getRejectedReason()
 * @method Campaign setRejectedReason(int $reason)
 * @method string getChecksum()
 * @method Campaign setChecksum(string $checksum)
 * @method string getBoostType()
 * @method Campaign setBoostType()
 * @method string getName()
 * @method Campaign setName(string $name)
 * @method int getStart()
 * @method Campaign setStart(int $start)
 * @method int getEnd()
 * @method Campaign setEnd(int $end)
 * @method int getBudget()
 * @method Campaign setBudget(int $budget)
 * @method int getDailyCap()
 * @method Campaign setDailyCap(int $dailyCap)
 * @method int getPaused()
 * @method Campaign setPaused(int $paused)
 * @method int getTodayImpressions()
 * @method Campaign setTodayImpressions($todayImpressions)
 */
class Campaign extends Boost implements \JsonSerializable
{
    use MagicAttributes;

    const STATE_PENDING = 'pending';

    /** @var string $boostType */
    protected $boostType = self::BOOST_TYPE_CAMPAIGN;
    /** @var string $name */
    protected $name;
    /** @var int $start */
    protected $start;
    /** @var int $end */
    protected $end;
    /** @var int $budget */
    protected $budget;
    /** @var int $dailyCap */
    protected $dailyCap;
    /** @var bool $paused */
    protected $paused;
    /** @var int $todayImpressions */
    protected $todayImpressions = 0;

    public function export($fields = []): array
    {
        $boostExport = parent::export($fields);
        $campaignExport = [
            'name' => $this->name,
            'start' => $this->start,
            'end' => $this->end,
            'budget' => $this->budget,
            'daily_cap' => $this->dailyCap,
            'delivery_status' => $this->getDeliveryStatus(),
            'cpm' => $this->cpm(),
            'urn' => $this->getUrn(),
            'today_impressions' => $this->todayImpressions
        ];

        return array_merge($boostExport, $campaignExport);
    }

    public function getDeliveryStatus(): string
    {
        if ($this->completedTimestamp) {
            return self::STATE_COMPLETED;
        } elseif ($this->rejectedTimestamp) {
            return self::STATE_REJECTED;
        } elseif ($this->revokedTimestamp) {
            return self::STATE_REVOKED;
        } elseif ($this->reviewedTimestamp) {
            return self::STATE_APPROVED;
        } elseif ($this->createdTimestamp) {
            return self::STATE_CREATED;
        }

        return self::STATE_PENDING;
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
        return $this->getDeliveryStatus() === self::STATE_APPROVED;
    }

    public function shouldBeStarted(int $now): bool
    {
        $isCreated = $this->getDeliveryStatus() === self::STATE_CREATED;
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
        return !in_array($this->getDeliveryStatus(), [self::STATE_PENDING, self::STATE_CREATED], true);
    }

    public function hasFinished(): bool
    {
        return in_array($this->getDeliveryStatus(), [
            self::STATE_COMPLETED,
            self::STATE_REJECTED,
            self::STATE_REVOKED,
        ], false);
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
        $data = $this->export();
        /* TODO: Filter data here */
        return $data;
    }

    public function getUrn(): string
    {
        if (empty($this->guid)) {
            throw new \Exception('No Guid');
        }

        return "urn:campaign:{$this->guid}";
    }

    public function setUrn(string $urn): self
    {
        $urn = new Urn($urn);
        $this->guid = $urn->getNss();
    }

    public function pause(): self
    {
        $this->paused = 1;
        return $this;
    }

    public function unpause(): self
    {
        $this->paused = 0;
        return $this;
    }
}
