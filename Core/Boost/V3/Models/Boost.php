<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Models;

use Minds\Common\Access;
use Minds\Entities\EntityInterface;
use Minds\Entities\ExportableInterface;
use Minds\Traits\MagicAttributes;

/**
 * Class representing a V3 boost
 *
 * @method self setGuid(string $guid)
 * @method self setOwnerGuid(string $ownerGuid)
 * @method self setEntityGuid(string $entityGuid)
 * @method string getEntityGuid()
 * @method self setTargetLocation(int $targetLocation)
 * @method int getTargetLocation()
 * @method self setTargetSuitability(int $targetSuitability)
 * @method int getTargetSuitability()
 * @method self setPaymentMethod(int $paymentMethod)
 * @method int getPaymentMethod()
 * @method self setPaymentMethodId(string|null $paymentMethodId)
 * @method string|null getPaymentMethodId()
 * @method self setPaymentAmount(float $paymentAmount)
 * @method float getPaymentAmount()
 * @method self setPaymentTxId(string|null $paymentTxId)
 * @method string|null getPaymentTxId()
 * @method self setDailyBid(float $dailyBid)
 * @method float getDailyBid()
 * @method self setDurationDays(int $durationDays)
 * @method int getDurationDays()
 * @method self setStatus(int $status)
 * @method int getStatus()
 * @method self setRejectionReason(int $rejectionStatus)
 * @method int getRejectionReason()
 * @method self setCreatedTimestamp(int $createdTimestamp)
 * @method int getCreatedTimestamp()
 * @method self setUpdatedTimestamp(int|null $updatedTimestamp)
 * @method int|null getUpdatedTimestamp()
 * @method self setApprovedTimestamp(int|null $approvedTimestamp)
 * @method int|null getApprovedTimestamp()
 * @method self setEntity(EntityInterface $entity)
 */
class Boost implements EntityInterface, ExportableInterface
{
    use MagicAttributes;

    private string $guid;
    private string $ownerGuid;
    private ?EntityInterface $entity = null;


    public function __construct(
        private string $entityGuid,
        private int $targetLocation,
        private int $targetSuitability,
        private int $paymentMethod,
        private float $paymentAmount,
        private float $dailyBid,
        private int $durationDays,
        private ?int $status = null,
        private ?int $rejectionReason = null,
        private ?int $createdTimestamp = null,
        private ?string $paymentTxId = null,
        private ?int $updatedTimestamp = null,
        private ?int $approvedTimestamp = null
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getGuid(): string
    {
        return $this->guid;
    }

    /**
     * @inheritDoc
     */
    public function getOwnerGuid(): string
    {
        return $this->ownerGuid;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return 'boost';
    }

    /**
     * @inheritDoc
     */
    public function getSubtype(): ?string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getAccessId(): string
    {
        return (string) Access::PUBLIC;
    }

    public function getUrn(): string
    {
        return 'urn:boost:' . $this->getGuid();
    }

    /**
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        return [
            'guid' => $this->getGuid(),
            'urn' => $this->getUrn(),
            'owner_guid' => $this->getOwnerGuid(),
            'entity_guid' => $this->getEntityGuid(),
            'entity' => $this->entity?->export(),
            'target_location' => $this->getTargetLocation(),
            'target_suitability' => $this->getTargetSuitability(),
            'payment_method' => $this->getPaymentMethod(),
            'payment_amount' => $this->getPaymentAmount(),
            'daily_bid' => $this->getDailyBid(),
            'duration_days' => $this->getDurationDays(),
            'boost_status' => $this->getStatus(),
            'rejection_reason' => $this->getRejectionReason(),
            'created_timestamp' => $this->getCreatedTimestamp(),
            'updated_timestamp' => $this->getUpdatedTimestamp(),
            'approved_timestamp' => $this->getApprovedTimestamp(),
        ];
    }
}
