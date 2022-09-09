<?php

declare(strict_types=1);

namespace Minds\Core\Supermind;

use Iterator;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOException;
use PDOStatement;

class Repository
{
    private PDO $mysqlClientReader;
    private PDO $mysqlClientWriter;

    /**
     * @param MySQLClient|null $mysqlHandler
     * @param EntitiesBuilder|null $entitiesBuilder
     * @throws ServerErrorException
     */
    public function __construct(
        private ?MySQLClient $mysqlHandler = null,
        private ?EntitiesBuilder $entitiesBuilder = null
    ) {
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->mysqlHandler ??= Di::_()->get("Database\MySQL\Client");
        $this->mysqlClientReader = $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_REPLICA);
        $this->mysqlClientWriter = $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_MASTER);
    }

    public function beginTransaction(): void
    {
        if ($this->mysqlClientWriter->inTransaction()) {
            throw new PDOException("Cannot initiate transaction. Previously initiated transaction still in progress.");
        }

        $this->mysqlClientWriter->beginTransaction();
    }

    public function rollbackTransaction(): void
    {
        if ($this->mysqlClientWriter->inTransaction()) {
            $this->mysqlClientWriter->rollBack();
        }
    }

    public function commitTransaction(): void
    {
        $this->mysqlClientWriter->commit();
    }

    /**
     * @param string $receiverGuid
     * @param int $offset
     * @param int $limit
     * @return Iterator
     */
    public function getReceivedRequests(string $receiverGuid, int $offset, int $limit): Iterator
    {
        $statement = $this->buildReceivedRequestsQuery($receiverGuid, $offset, $limit);
        $statement->execute();

        $i = 0;
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $request = SupermindRequest::fromData($row);
            if ($i < 12) {
                $request->setEntity($this->entitiesBuilder->single($request->getActivityGuid()));
            }
            yield $request;
            $i++;
        }
    }

    /**
     * @param string $receiverGuid
     * @param int $offset
     * @param int $limit
     * @return PDOStatement
     */
    private function buildReceivedRequestsQuery(string $receiverGuid, int $offset, int $limit): PDOStatement
    {
        $query = "SELECT
                *
            FROM
                superminds
            WHERE
                receiver_guid = :receiver_guid and status != :status
            ORDER BY
                created_timestamp DESC
            LIMIT
                :offset, :limit";
        $values = [
            'receiver_guid' => $receiverGuid,
            'status' => SupermindRequestStatus::PENDING,
            'offset' => $offset,
            'limit' => $limit
        ];

        $statement = $this->mysqlClientReader->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);
        return $statement;
    }

    /**
     * @param string $senderGuid
     * @param int $offset
     * @param int $limit
     * @return Iterator
     */
    public function getSentRequests(string $senderGuid, int $offset, int $limit): Iterator
    {
        $statement = $this->buildSentRequestsQuery($senderGuid, $offset, $limit);
        $statement->execute();

        $i = 0;
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $request = SupermindRequest::fromData($row);
            if ($i < 12) {
                $request->setEntity($this->entitiesBuilder->single($request->getActivityGuid()));
                $request->setReceiverEntity($this->entitiesBuilder->single($request->getReceiverGuid()));
            }
            yield $request;
            $i++;
        }
    }

    private function buildSentRequestsQuery(string $senderGuid, int $offset, int $limit): PDOStatement
    {
        $query = "SELECT
                *
            FROM
                superminds
            WHERE
                sender_guid = :sender_guid and status != :status
            ORDER BY
                created_timestamp DESC
            LIMIT
                :offset, :limit";
        $values = [
            'sender_guid' => $senderGuid,
            'status' => SupermindRequestStatus::PENDING,
            'offset' => $offset,
            'limit' => $limit
        ];

        $statement = $this->mysqlClientReader->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);
        return $statement;
    }

    /**
     * @param SupermindRequest $request
     * @return bool
     * @throws PDOException
     */
    public function addSupermindRequest(SupermindRequest $request): bool
    {
        $statement = $this->buildNewSupermindRequestQuery($request);
        $statement->execute();

        return true;
    }

    /**
     * @param SupermindRequest $request
     * @return PDOStatement
     */
    private function buildNewSupermindRequestQuery(SupermindRequest $request): PDOStatement
    {
        $query = "INSERT INTO
                superminds (guid, sender_guid, receiver_guid, status, payment_amount, payment_method, payment_reference, created_timestamp, twitter_required, reply_type)
            VALUES
                (:guid, :sender_guid, :receiver_guid, :status, :payment_amount, :payment_method, :payment_reference, :created_timestamp, :twitter_required, :reply_type)";
        $values = [
            "guid" => $request->getGuid(),
            "sender_guid" => $request->getSenderGuid(),
            "receiver_guid" => $request->getReceiverGuid(),
            "status" => SupermindRequestStatus::PENDING,
            "payment_amount" => $request->getPaymentAmount(),
            "payment_method" => $request->getPaymentMethod(),
            "payment_reference" => $request->getPaymentTxID(),
            "created_timestamp" => date('c', time()),
            "twitter_required" => $request->getTwitterRequired(),
            "reply_type" => $request->getReplyType()
        ];

        $statement = $this->mysqlClientWriter->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);
        return $statement;
    }

    /**
     * @param int $status
     * @param string $supermindRequestId
     * @return bool
     */
    public function updateSupermindRequestStatus(int $status, string $supermindRequestId): bool
    {
        $statement = "UPDATE superminds SET status = :status, updated_timestamp = :updated_timestamp WHERE guid = :guid";
        $values = [
            "status" => $status,
            'updated_timestamp' => date('c', time()),
            "guid" => $supermindRequestId
        ];

        $statement = $this->mysqlClientWriter->prepare($statement);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();

        return true;
    }

    /**
     * @param string $supermindRequestId
     * @return SupermindRequest|null
     */
    public function getSupermindRequest(string $supermindRequestId): ?SupermindRequest
    {
        $statement = "SELECT * FROM superminds WHERE guid = :guid";
        $values = [
            "guid" => $supermindRequestId
        ];

        $statement = $this->mysqlClientReader->prepare($statement);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();

        if ($statement->rowCount() === 0) {
            return null;
        }

        return SupermindRequest::fromData(
            $statement->fetch(PDO::FETCH_ASSOC)
        );
    }

    /**
     * @param string $supermindRequestId
     * @param int $activityGuid
     * @return bool
     */
    public function updateSupermindRequestActivityGuid(string $supermindRequestId, int $activityGuid): bool
    {
        $statement = "UPDATE superminds SET activity_guid = :activity_guid, status = :status, updated_timestamp = :update_timestamp WHERE guid = :guid";
        $values = [
            'activity_guid' => $activityGuid,
            'status' => SupermindRequestStatus::CREATED,
            'update_timestamp' => date('c', time()),
            'guid' => $supermindRequestId
        ];

        $statement = $this->mysqlClientWriter->prepare($statement);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();

        if ($statement->rowCount() === 0) {
            return false;
        }

        return true;
    }

    /**
     * @param string $supermindRequestId
     * @return bool
     */
    public function deleteSupermindRequest(string $supermindRequestId): bool
    {
        $statement = "DELETE FROM superminds WHERE guid = :guid";
        $values = [
            'guid' => $supermindRequestId
        ];

        $statement = $this->mysqlClientWriter->prepare($statement);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();

        if ($statement->rowCount() === 0) {
            return false;
        }

        return true;
    }

    /**
     * @param int $thresholdInSeconds
     * @return bool
     */
    public function expireSupermindRequests(int $thresholdInSeconds): bool
    {
        $statement = "UPDATE superminds SET status = :target_status WHERE status = :created_status AND created_timestamp <= :target_timestamp";
        $values = [
            "target_status" => SupermindRequestStatus::EXPIRED,
            "created_status" => SupermindRequestStatus::CREATED,
            "target_timestamp" => date('c', strtotime("-${thresholdInSeconds} seconds"))
        ];

        $statement = $this->mysqlClientWriter->prepare($statement);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();

        return true;
    }
}
