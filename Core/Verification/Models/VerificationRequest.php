<?php
declare(strict_types=1);

namespace Minds\Core\Verification\Models;

use Minds\Common\Access;
use Minds\Entities\EntityInterface;
use Minds\Entities\ExportableInterface;
use Minds\Traits\MagicAttributes;

/**
 * @method self setUserGuid(string $userGuid)
 * @method string getUserGuid()
 * @method self setDeviceId(string $deviceId)
 * @method string getDeviceId()
 * @method self setDeviceToken(string $deviceToken)
 * @method string getDeviceToken()
 * @method self setVerificationCode(string $verificationCode)
 * @method string getVerificationCode()
 * @method self setStatus(int $status)
 * @method self setCreatedAt(int $createdAt)
 * @method int getCreatedAt()
 * @method self setUpdatedAt(int $updatedAt)
 * @method int|null getUpdatedAt()
 * @method self setSensorData(string $sensorData)
 * @method string|null getSensorData()
 * @method self setIpAddr(string $ipAddr)
 * @method string getIpAddr()
 * @method self setGeo(string $geo)
 * @method string getGeo()
 */
class VerificationRequest implements ExportableInterface, EntityInterface
{
    use MagicAttributes;

    private const EXPIRY_THRESHOLD = 900; // in seconds

    private const URN_METHOD = 'user-verification';

    private string $userGuid;
    private string $deviceId;
    private string $deviceToken;
    private string $verificationCode;
    private int $status = VerificationRequestStatus::PENDING;
    private int $createdAt;
    private ?int $updatedAt = null;
    private ?string $sensorData = null;
    private string $ipAddr;
    private string $geo;

    public function __construct(array $data = [])
    {
        if (isset($data['user_guid'])) {
            $this->setUserGuid($data['user_guid']);
        }

        if (isset($data['device_id'])) {
            $this->setDeviceId($data['device_id']);
        }

        if (isset($data['device_token'])) {
            $this->setDeviceToken($data['device_token']);
        }

        if (isset($data['verification_code'])) {
            $this->setVerificationCode($data['verification_code']);
        }

        if (isset($data['status'])) {
            $this->setStatus($data['status']);
        }

        if (isset($data['created_at'])) {
            $this->setCreatedAt(strtotime($data['created_at']));
        } else {
            $this->setCreatedAt(time());
        }

        if (isset($data['updated_at'])) {
            $this->setUpdatedAt(strtotime($data['updated_at']));
        }

        if (isset($data['sensor_data'])) {
            $this->setSensorData($data['sensor_data']);
        }

        if (isset($data['ip'])) {
            $this->setIpAddr($data['ip']);
        }

        if (isset($data['geo_lat']) && isset($data['geo_lon'])) {
            $this->setGeo("{$data['geo_lat']},{$data['geo_lon']}");
        }
    }

    public function isExpired(): bool
    {
        return $this->status === VerificationRequestStatus::EXPIRED || time() > ($this->createdAt + self::EXPIRY_THRESHOLD);
    }

    public function isVerified(): bool
    {
        return $this->status === VerificationRequestStatus::VERIFIED;
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
            'request_status' => $this->getStatus(),
            'created_at' => $this->getCreatedAt(),
            'updated_at' => $this->getUpdatedAt()
        ];
    }

    public function getGuid(): ?string
    {
        return null;
    }

    public function getOwnerGuid(): ?string
    {
        return $this->getUserGuid();
    }

    public function getType(): ?string
    {
        return 'user-verification';
    }

    public function getSubtype(): ?string
    {
        return null;
    }

    public function getUrn(): string
    {
        return 'urn:' . self::URN_METHOD . ':' . $this->getGuid();
    }

    public function getAccessId(): string
    {
        return (string) Access::PUBLIC;
    }
}
