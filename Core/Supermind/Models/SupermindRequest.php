<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Models;

use Minds\Common\Access;
use Minds\Core\Supermind\SupermindRequestPaymentMethod;
use Minds\Core\Supermind\SupermindRequestReplyType;
use Minds\Core\Supermind\SupermindRequestStatus;
use Minds\Entities\EntityInterface;
use Minds\Entities\ExportableInterface;
use Minds\Traits\MagicAttributes;

/**
 * @method self setGuid(string $guid)
 * @method string getActivityGuid()
 * @method self setActivityGuid(string $guid)
 * @method string getSenderGuid()
 * @method self setSenderGuid(string $senderGuid)
 * @method string getReceiverGuid()
 * @method self setReceiverGuid(string $receiverGuid)
 * @method int getStatus()
 * @method self setStatus(int $status)
 * @method float getPaymentAmount()
 * @method self setPaymentAmount(float $paymentAmount)
 * @method int getPaymentMethod()
 * @method self setPaymentMethod(int $paymentMethod)
 * @method string getPaymentTxID()
 * @method self setPaymentTxID(string $paymentTxID)
 * @method int getCreatedAt()
 * @method self setCreatedAt(int $createdAt)
 * @method int getUpdatedAt()
 * @method self setUpdatedAt(int $updatedAt)
 * @method bool getTwitterRequired()
 * @method self setTwitterRequired(bool $twitterRequired)
 * @method bool getReplyType()
 * @method self setReplyType(bool $twitterRequired)
 * @method null|EntityInterface getEntity()
 * @method self setEntity(EntityInterface $entity)
 * @method null|EntityInterface getReceiverEntity()
 * @method self setReceiverEntity(EntityInterface $receiverEntity)
 */
class SupermindRequest implements ExportableInterface, EntityInterface
{
    use MagicAttributes;

    /**
     * @const int Represents the threshold expressed in seconds used to consider a Supermind request expired.
     */
    public const SUPERMIND_EXPIRY_THRESHOLD = 7 * 86400;
    public const URN_METHOD = 'supermind';

    private string $guid;
    private string $activityGuid;
    private string $senderGuid;
    private string $receiverGuid;
    private int $status = SupermindRequestStatus::PENDING;
    private float $paymentAmount;
    private int $paymentMethod = SupermindRequestPaymentMethod::OFFCHAIN_TOKEN;
    private ?string $paymentTxID = null;
    private ?int $createdAt = null;
    private ?int $updatedAt = null;
    private bool $twitterRequired = false;
    private int $replyType = SupermindRequestReplyType::TEXT;
    private ?EntityInterface $entity = null;
    private ?EntityInterface $receiverEntity = null;

    public static function fromData(array $data): self
    {
        $request = new SupermindRequest();

        if (isset($data['guid'])) {
            $request->setGuid($data['guid']);
        }

        if (isset($data['activity_guid'])) {
            $request->setActivityGuid($data['activity_guid']);
        }

        if (isset($data['sender_guid'])) {
            $request->setSenderGuid($data['sender_guid']);
        }

        if (isset($data['receiver_guid'])) {
            $request->setReceiverGuid($data['receiver_guid']);
        }

        if (isset($data['status'])) {
            $request->setStatus($data['status']);
        }

        if (isset($data['payment_amount'])) {
            $request->setPaymentAmount($data['payment_amount']);
        }

        if (isset($data['payment_method'])) {
            $request->setPaymentMethod($data['payment_method']);
        }

        if (isset($data['payment_reference'])) {
            $request->setPaymentTxID($data['payment_reference']);
        }

        if (isset($data['created_timestamp'])) {
            $request->setCreatedAt(strtotime($data['created_timestamp']));
        }

        if (isset($data['updated_timestamp'])) {
            $request->setUpdatedAt(strtotime($data['updated_timestamp']));
        }

        if (isset($data['twitter_required'])) {
            $request->setTwitterRequired($data['twitter_required']);
        }

        if (isset($data['reply_type'])) {
            $request->setReplyType($data['reply_type']);
        }

        return $request;
    }

    public function isExpired(): bool
    {
        return time() >= ($this->createdAt + self::SUPERMIND_EXPIRY_THRESHOLD);
    }

    /**
     * @return string
     * */
    public function getGuid(): ?string
    {
        return $this->guid;
    }

    /**
     * @return string
     */
    public function getOwnerGuid(): ?string
    {
        return $this->senderGuid;
    }


    /**
     * @return string
     */
    public function getType(): string
    {
        return 'supermind';
    }

    /**
     * @return string
     */
    public function getSubtype(): ?string
    {
        return null;
    }

    /**
     * @return string
     */
    public function getUrn(): string
    {
        return 'urn:' . self::URN_METHOD . ':' . $this->getGuid();
    }

    /**
     * @return string
     */
    public function getAccessId(): string
    {
        return (string) Access::PUBLIC;
    }

    /**
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        return [
            "guid" => $this->guid,
            "activity_guid" => $this->activityGuid,
            "sender_guid" => $this->senderGuid,
            "receiver_guid" => $this->receiverGuid,
            "status" => $this->status,
            "payment_amount" => $this->paymentAmount,
            "payment_method" => $this->paymentMethod,
            "payment_txid" => $this->paymentTxID,
            "created_timestamp" => $this->createdAt,
            "expiry_threshold" => self::SUPERMIND_EXPIRY_THRESHOLD,
            "updated_timestamp" => $this->updatedAt,
            "twitter_required" => $this->twitterRequired,
            "reply_type" => $this->replyType,
            "entity" => $this->entity?->export(),
            "receiver_entity" => $this->receiverEntity?->export()
        ];
    }
}
