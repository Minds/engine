<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Models;

use Minds\Entities\EntityInterface;
use Minds\Entities\ExportableInterface;
use Minds\Traits\MagicAttributes;

/**
 * Class representing a V3 boost
 *
 * @method self setGuid(string $guid)
 * @method string getGuid()
 * @method self setOwnerGuid(string $ownerGuid)
 * @method string getOwnerGuid()
 * @method self setEntityGuid(string $entityGuid)
 * @method string getEntityGuid()
 * @method self setTargetLocation(int $targetLocation)
 * @method int getTargetLocation()
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
 * @method self setCreatedTimestamp(int $createdTimestamp)
 * @method int getCreatedTimestamp()
 * @method self setUpdatedTimestamp(int|null $updatedTimestamp)
 * @method int|null getUpdatedTimestamp()
 * @method self setApprovedTimestamp(int|null $approvedTimestamp)
 * @method int|null getApprovedTimestamp()
 * @method self setEntity(EntityInterface $entity)
 */
class Boost implements ExportableInterface
{
    use MagicAttributes;

    private string $guid;
    private string $ownerGuid;
    private string $entityGuid;
    private int $targetLocation;
    private int $paymentMethod;
    private ?string $paymentMethodId;
    private float $paymentAmount;
    private ?string $paymentTxId;
    private float $dailyBid;
    private int $durationDays;
    private int $status;
    private int $createdTimestamp;
    private ?int $updatedTimestamp = null;
    private ?int $approvedTimestamp = null;
    private ?EntityInterface $entity = null;


    public function __construct(array $data)
    {
        if (isset($data['guid'])) {
            $this->setGuid($data['guid']);
        }
        if (isset($data['owner_guid'])) {
            $this->setOwnerGuid($data['owner_guid']);
        }
        if (isset($data['entity_guid'])) {
            $this->setEntityGuid($data['entity_guid']);
        }
        if (isset($data['target_location'])) {
            $this->setTargetLocation($data['target_location']);
        }
        if (isset($data['payment_method'])) {
            $this->setPaymentMethod($data['payment_method']);
        }
        if (isset($data['payment_method'])) {
            $this->setPaymentMethod($data['payment_method']);
        }
        if (isset($data['payment_amount'])) {
            $this->setPaymentAmount($data['payment_amount']);
        }
        if (isset($data['payment_tx_id'])) {
            $this->setPaymentTxId($data['payment_tx_id']);
        }
        if (isset($data['daily_bid'])) {
            $this->setDailyBid($data['daily_bid']);
        }
        if (isset($data['duration_days'])) {
            $this->setDurationDays($data['duration_days']);
        }
        if (isset($data['status'])) {
            $this->setStatus($data['status']);
        }
        if (isset($data['created_timestamp'])) {
            $this->setCreatedTimestamp(strtotime($data['created_timestamp']));
        }
        if (isset($data['updated_timestamp']) && $data['updated_timestamp']) {
            $this->setUpdatedTimestamp(strtotime($data['updated_timestamp']));
        }
        if (isset($data['approved_timestamp']) && $data['approved_timestamp']) {
            $this->setApprovedTimestamp(strtotime($data['approved_timestamp']));
        }
    }

    /**
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        return [
            'guid' => $this->getGuid(),
            'owner_guid' => $this->getOwnerGuid(),
            'entity_guid' => $this->getEntityGuid(),
            'entity' => $this->entity,
            'target_location' => $this->getTargetLocation(),
            'payment_method' => $this->getPaymentMethod(),
            'payment_amount' => $this->getPaymentAmount(),
            'daily_bid' => $this->getDailyBid(),
            'duration_days' => $this->getDurationDays(),
            'status' => $this->getStatus(),
            'created_timestamp' => $this->getCreatedTimestamp(),
            'updated_timestamp' => $this->getUpdatedTimestamp(),
            'approved_timestamp' => $this->getApprovedTimestamp(),
        ];
    }
}
