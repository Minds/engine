<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Models;

use Minds\Common\Access;
use Minds\Core\Boost\V3\Enums\BoostStatus;
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
 * @method getEntity()
 * @method int getTargetLocation()
 * @method self setTargetSuitability(int $targetSuitability)
 * @method int getTargetSuitability()
 * @method self setPaymentMethod(int $paymentMethod)
 * @method int getPaymentMethod()
 * @method self setPaymentGuid(int $paymentGuid)
 * @method int getPaymentGuid()
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
 * @method self setRejectionReason(int $rejectionStatus)
 * @method int|null getRejectionReason()
 * @method self setCreatedTimestamp(int $createdTimestamp)
 * @method int getCreatedTimestamp()
 * @method self setUpdatedTimestamp(int|null $updatedTimestamp)
 * @method int|null getUpdatedTimestamp()
 * @method self setApprovedTimestamp(int|null $approvedTimestamp)
 * @method int|null getApprovedTimestamp()
 * @method self setEntity(EntityInterface $entity)
 * @method self setTargetPlatformWeb(bool $targetPlatformWeb)
 * @method bool getTargetPlatformWeb()
 * @method self setTargetPlatformAndroid(bool $targetPlatformAndroid)
 * @method bool getTargetPlatformAndroid()
 * @method self setTargetPlatformIos(bool $targetPlatformIos)
 * @method bool getTargetPlatformIos()
 * @method self setGoal(int $goal)
 * @method int getGoal()
 * @method self setGoalButtonText(int $goalButtonText)
 * @method int getGoalButtonText()
 * @method self setGoalButtonUrl(string $goalButtonUrl)
 * @method string getGoalButtonUrl()
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
        private ?int $goal = null,
        private ?int $goalButtonText = null,
        private ?string $goalButtonUrl = null,
        private ?int $status = null,
        private ?int $rejectionReason = null,
        private ?int $createdTimestamp = null,
        private ?string $paymentTxId = null,
        private ?int $updatedTimestamp = null,
        private ?int $approvedTimestamp = null,
        private ?int $summaryViewsDelivered = 0,
        private ?int $summaryClicksDelivered = 0,
        private ?int $paymentGuid = null,
        private ?bool $targetPlatformWeb = true,
        private ?bool $targetPlatformAndroid = true,
        private ?bool $targetPlatformIos = true,
        private ?string $paymentMethodId = null
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

    /**~
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

    public function getStatus(): ?int
    {
        return
            $this->getApprovedTimestamp() !== null &&
            ($this->status === BoostStatus::COMPLETED || time() >= ($this->getApprovedTimestamp() + strtotime("$this->durationDays day", 0))) ?
                BoostStatus::COMPLETED :
                $this->status;
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
            'target_platform_web' =>$this->getTargetPlatformWeb(),
            'target_platform_android' =>$this->getTargetPlatformAndroid(),
            'target_platform_ios' =>$this->getTargetPlatformIos(),
            'goal' => $this->getGoal(),
            'goal_button_text' => $this->getGoalButtonText(),
            'goal_button_url' =>$this->getGoalButtonUrl(),
            'payment_tx_id' => $this->getPaymentTxId(),
            'payment_method' => $this->getPaymentMethod(),
            'payment_amount' => $this->getPaymentAmount(),
            'daily_bid' => $this->getDailyBid(),
            'duration_days' => $this->getDurationDays(),
            'boost_status' => $this->getStatus(),
            'rejection_reason' => $this->getRejectionReason(),
            'created_timestamp' => $this->getCreatedTimestamp(),
            'updated_timestamp' => $this->getUpdatedTimestamp(),
            'approved_timestamp' => $this->getApprovedTimestamp(),
            'summary' => [
                'views_delivered' => $this->summaryViewsDelivered,
                'total_clicks' => $this->summaryClicksDelivered,
            ],
        ];
    }
}
