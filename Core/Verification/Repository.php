<?php
declare(strict_types=1);

namespace Minds\Core\Verification;

use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Di\Di;
use Minds\Core\Verification\Exceptions\VerificationRequestNotFoundException;
use Minds\Core\Verification\Models\VerificationRequest;
use Minds\Core\Verification\Models\VerificationRequestStatus;
use Minds\Exceptions\ServerErrorException;
use PDO;

class Repository
{
    private PDO $mysqlClientReader;
    private PDO $mysqlClientWriter;

    /**
     * @param MySQLClient|null $mysqlHandler
     * @throws ServerErrorException
     */
    public function __construct(
        private ?MySQLClient $mysqlHandler = null
    ) {
        $this->mysqlHandler ??= Di::_()->get("Database\MySQL\Client");
        $this->mysqlClientReader = $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_REPLICA);
        $this->mysqlClientWriter = $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_MASTER);
    }

    /**
     * @param string $userGuid
     * @param string $deviceId
     * @return VerificationRequest
     * @throws ServerErrorException
     * @throws VerificationRequestNotFoundException
     */
    public function getVerificationRequestDetails(string $userGuid, string $deviceId): VerificationRequest
    {
        $query = "SELECT * FROM user_verification WHERE user_guid = :user_guid AND device_id = :device_id ORDER BY created_at desc limit 1";
        $values = [
            'user_guid' => $userGuid,
            'device_id' => $deviceId
        ];

        $statement = $this->mysqlClientReader->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        if (!$statement->execute()) {
            throw new ServerErrorException();
        }

        if ($statement->rowCount() === 0) {
            throw new VerificationRequestNotFoundException();
        }

        return new VerificationRequest($statement->fetch(PDO::FETCH_ASSOC));
    }

    /**
     * @param VerificationRequest $verificationRequest
     * @return bool
     */
    public function createVerificationRequest(VerificationRequest $verificationRequest): bool
    {
        $query = "INSERT INTO user_verification (
                user_guid,
                device_id,
                device_token,
                status,
                verification_code,
                ip
            ) VALUES (
                :user_guid,
                :device_id,
                :device_token,
                :status,
                :verification_code,
                :ip
            )";
        $values = [
            'user_guid' => $verificationRequest->getUserGuid(),
            'device_id' => $verificationRequest->getDeviceId(),
            'device_token' => $verificationRequest->getDeviceToken(),
            'status' => $verificationRequest->getStatus(),
            'verification_code' => $verificationRequest->getVerificationCode(),
            'ip' => $verificationRequest->getIpAddr(),
        ];

        $statement = $this->mysqlClientWriter->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        return $statement->execute();
    }

    /**
     * @param VerificationRequest $verificationRequest,
     * @param int $status
     * @return bool
     */
    public function updateVerificationRequestStatus(VerificationRequest $verificationRequest, int $status): bool
    {
        $query = "UPDATE user_verification 
            SET status = :status,
                updated_at = :updated_at
            WHERE user_guid = :user_guid
                AND device_id = :device_id
                AND created_at = :created_at";
        $values = [
            // Where clause
            'user_guid' => $verificationRequest->getUserGuid(),
            'device_id' => $verificationRequest->getDeviceId(),
            'created_at' => date('c', $verificationRequest->getCreatedAt()),
            // Update values
            'status' => $status,
            'updated_at' => date('c', time()),
        ];

        $statement = $this->mysqlClientWriter->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        return $statement->execute();
    }

    /**
     * @param VerificationRequest $verificationRequest,
     * @param string $geo
     * @param string|null $sensorData
     * @return bool
     */
    public function markRequestAsVerified(
        VerificationRequest $verificationRequest,
        string $geo,
        ?string $sensorData = null
    ): bool {
        [ $geoLat, $geoLon ] = explode(',', $geo);

        $query = "UPDATE user_verification 
            SET status = :status, 
                updated_at = :updated_at,
                sensor_data = :sensor_data,
                geo_lat = :geo_lat,
                geo_lon = :geo_lon
            WHERE user_guid = :user_guid 
                AND device_id = :device_id
                AND created_at = :created_at";

        $values = [
            // Where clause
            'user_guid' => $verificationRequest->getUserGuid(),
            'device_id' => $verificationRequest->getDeviceId(),
            'created_at' => date('c', $verificationRequest->getCreatedAt()),
            // Update value
            'status' => VerificationRequestStatus::VERIFIED,
            'updated_at' => date('c', time()),
            'sensor_data' => $sensorData,
            'geo_lat' => $geoLat,
            'geo_lon' => $geoLon,
        ];

        $statement = $this->mysqlClientWriter->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        return $statement->execute();
    }
}
