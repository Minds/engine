<?php
declare(strict_types=1);

namespace Minds\Core\Verification\Models;

use Minds\Entities\ExportableInterface;
use Minds\Traits\MagicAttributes;

/**
 * @method self setUserGuid(string $userGuid)
 * @method string getUserGuid()
 * @method self setDeviceId(string $deviceId)
 * @method string getDeviceId()
 * @method self setVerificationCode(string $verificationCode)
 * @method string getVerificationCode()
 * @method self setStatus(int $status)
 * @method self setCreatedAt(int $createdAt)
 * @method int getCreatedAt()
 * @method self setUpdatedAt(int $updatedAt)
 * @method int|null getUpdatedAt()
 * @method self setSensorData(string $sensorData)
 * @method string|null getSensorData()
 */
class VerificationRequest implements ExportableInterface
{
    use MagicAttributes;

    private const EXPIRY_THRESHOLD = 900; // in seconds

    private string $userGuid;
    private string $deviceId;
    private string $verificationCode;
    private int $status;
    private int $createdAt;
    private ?int $updatedAt = null;
    private ?string $sensorData = null;

    public function withData(array $data): self
    {
        $instance = clone $this;

        if (isset($data['user_guid'])) {
            $instance->setUserGuid($data['user_guid']);
        }

        if (isset($data['device_id'])) {
            $instance->setDeviceId($data['device_id']);
        }

        if (isset($data['verification_code'])) {
            $instance->setVerificationCode($data['verification_code']);
        }

        if (isset($data['status'])) {
            $instance->setStatus($data['status']);
        }

        if (isset($data['created_at'])) {
            $instance->setCreatedAt((int) $data['created_at']);
        }

        if (isset($data['updated_at'])) {
            $instance->setUpdatedAt((int) $data['updated_at']);
        }

        if (isset($data['sensor_data'])) {
            $instance->setSensorData($data['sensor_data']);
        }

        return $instance;
    }

    public function isExpired(): bool
    {
        return $this->status === VerificationRequestStatus::EXPIRED || time() < ($this->createdAt + self::EXPIRY_THRESHOLD);
    }

    public function getStatus(): int
    {
        return $this->isExpired() ? VerificationRequestStatus::EXPIRED : $this->status;
    }

    /**
     * @inheritDoc
     */
    public function export(array $extras = []): array
    {
        return [
            'user_guid' => $this->getUserGuid(),
            'device_id' => $this->getDeviceId(),
            'status' => $this->getStatus(),
            'verification_code' => $this->getVerificationCode(),
            'created_at' => $this->getCreatedAt(),
            'updated_at' => $this->getUpdatedAt()
        ];
    }
}
