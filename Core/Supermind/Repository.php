<?php

declare(strict_types=1);

namespace Minds\Core\Supermind;

use Iterator;
use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOException;
use PDOStatement;
use Selective\Database\Operator;

class Repository extends AbstractRepository
{
    /**
     * @param MySQLClient|null $mysqlHandler
     * @param EntitiesBuilder|null $entitiesBuilder
     * @throws ServerErrorException
     */
    public function __construct(
        ?MySQLClient $mysqlHandler = null,
        ?Config $config = null,
        ?Logger $logger = null,
        private ?EntitiesBuilder $entitiesBuilder = null
    ) {
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        parent::__construct(
            mysqlHandler: $mysqlHandler ?? Di::_()->get('Database\MySQL\Client'),
            config: $config ?? Di::_()->get(Config::class),
            logger: $logger ?? Di::_()->get('Logger')
        );
    }

    /**
     * @param string $receiverGuid
     * @param int $offset
     * @param int $limit
     * @param SupermindRequestStatus|null $status
     * @return Iterator
     */
    public function getReceivedRequests(string $receiverGuid, int $offset, int $limit, ?SupermindRequestStatus $status): Iterator
    {
        $statement = $this->buildReceivedRequestsQuery(
            receiverGuid: $receiverGuid,
            offset: $offset,
            limit: $limit,
            status: $status->value ?? null
        );
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
     * @param int|null $status
     * @return PDOStatement
     */
    private function buildReceivedRequestsQuery(string $receiverGuid, int $offset, int $limit, ?int $status): PDOStatement
    {
        $values = [
            'receiver_guid' => $receiverGuid,
            'excludedStatus' => SupermindRequestStatus::PENDING->value,
            'offset' => $offset,
            'limit' => $limit
        ];

        $whereStatusClause = '';
        $createdAfterClause = '';
        if ($status) {
            $values['status'] = $status;
            $whereStatusClause = "AND status = :status";
        }

        // for created - we want to filter out any expired superminds not yet marked as expired.
        if ($status == SupermindRequestStatus::CREATED->value) {
            $values['min_timestamp'] = date('c', time() - SupermindRequest::SUPERMIND_EXPIRY_THRESHOLD);
            $createdAfterClause = 'AND created_timestamp >= :min_timestamp';
        }

        $query = "SELECT * FROM superminds
            WHERE receiver_guid = :receiver_guid AND status != :excludedStatus $whereStatusClause $createdAfterClause
            ORDER BY created_timestamp DESC
            LIMIT :offset, :limit
        ";

        $statement = $this->mysqlClientReader->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);
        return $statement;
    }

    /**
     * Get count of received requests.
     * @param string $receiverGuid - guid of receiver.
     * @param SupermindRequestStatus|null $status - status to count for (null will return all).
     * @return int count.
     */
    public function countReceivedRequests(string $receiverGuid, ?SupermindRequestStatus $status = null): int
    {
        $statement = $this->buildCountReceivedRequestsQuery(
            receiverGuid: $receiverGuid,
            status: $status->value ?? null
        );
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return (int) $result['count'] ?? 0;
    }

    /**
     * Build query to count received requests.
     * @param string $receiverGuid - guid of receiver.
     * @param int|null $status - status to count for (null will return all).
     * @return PDOStatement
     */
    private function buildCountReceivedRequestsQuery(string $receiverGuid, int $status = null): PDOStatement
    {
        $values = [
            'receiver_guid' => $receiverGuid,
            'excludedStatus' => SupermindRequestStatus::PENDING->value
        ];

        $whereStatusClause = '';
        $createdAfterClause = '';
        if ($status) {
            $values['status'] = $status;
            $whereStatusClause = "AND status = :status";

            // for created - we want to filter out any expired superminds not yet marked as expired.
            if ($status === SupermindRequestStatus::CREATED->value) {
                $values['min_timestamp'] = date('c', time() - SupermindRequest::SUPERMIND_EXPIRY_THRESHOLD);
                $createdAfterClause = 'AND created_timestamp >= :min_timestamp';
            }
        }

        $query = "SELECT COUNT(*) as count FROM superminds
            WHERE receiver_guid = :receiver_guid
            AND status != :excludedStatus $whereStatusClause $createdAfterClause
        ";

        $statement = $this->mysqlClientReader->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);
        return $statement;
    }

    /**
     * @param string $senderGuid
     * @param int $offset
     * @param int $limit
     * @param SupermindRequestStatus|null $status
     * @return Iterator
     */
    public function getSentRequests(string $senderGuid, int $offset, int $limit, ?SupermindRequestStatus $status): Iterator
    {
        $statement = $this->buildSentRequestsQuery(
            senderGuid: $senderGuid,
            offset: $offset,
            limit: $limit,
            status: $status->value ?? null
        );
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

    private function buildSentRequestsQuery(string $senderGuid, int $offset, int $limit, ?int $status): PDOStatement
    {
        $values = [
            'sender_guid' => $senderGuid,
            'excludedStatus' => SupermindRequestStatus::PENDING->value,
            'offset' => $offset,
            'limit' => $limit
        ];

        $whereStatusClause = '';
        $createdAfterClause = '';
        if ($status) {
            $values['status'] = $status;
            $whereStatusClause = "AND status = :status";
        }

        // for created - we want to filter out any expired superminds not yet marked as expired.
        if ($status == SupermindRequestStatus::CREATED->value) {
            $values['min_timestamp'] = date('c', time() - SupermindRequest::SUPERMIND_EXPIRY_THRESHOLD);
            $createdAfterClause = 'AND created_timestamp >= :min_timestamp';
        }

        $query = "SELECT * FROM superminds
            WHERE sender_guid = :sender_guid AND status != :excludedStatus $whereStatusClause $createdAfterClause
            ORDER BY created_timestamp DESC
            LIMIT :offset, :limit
        ";

        $statement = $this->mysqlClientReader->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);
        return $statement;
    }

    /**
     * Get count of sent requests.
     * @param string $senderGuid - guid of sender.
     * @param SupermindRequestStatus|null $status - status to count for (null will return all).
     * @return int count.
     */
    public function countSentRequests(string $senderGuid, ?SupermindRequestStatus $status = null): int
    {
        $statement = $this->buildCountSentRequestsQuery(
            senderGuid: $senderGuid,
            status: $status->value ?? null
        );
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return (int) $result['count'] ?? 0;
    }

    /**
     * Build query to count sent requests.
     * @param string $senderGuid - guid of sender.
     * @param int|null $status - status to count for (null will return all).
     * @return PDOStatement
     */
    private function buildCountSentRequestsQuery(string $senderGuid, int $status = null): PDOStatement
    {
        $values = [
            'sender_guid' => $senderGuid,
            'excludedStatus' => SupermindRequestStatus::PENDING->value
        ];

        $whereStatusClause = '';
        if ($status) {
            $values['status'] = $status;
            $whereStatusClause = "AND status = :status";
        }

        $query = "SELECT COUNT(*) as count FROM superminds
            WHERE sender_guid = :sender_guid AND status != :excludedStatus $whereStatusClause
        ";

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
            "status" => SupermindRequestStatus::PENDING->value,
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
     * @param SupermindRequestStatus $status
     * @param string $supermindRequestId
     * @return bool
     * @throws ServerErrorException
     */
    public function updateSupermindRequestStatus(SupermindRequestStatus $status, string $supermindRequestId): bool
    {
        $statement = "UPDATE superminds SET status = :status, updated_timestamp = :updated_timestamp WHERE guid = :guid";
        $values = [
            "status" => $status->value,
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

        /**
         * TODO: Use writer to ensure immediate consistency if making a write call
         * before this. Switch back to reader when refactored such that consistency
         * is not a pre-requisite.
         */
        $statement = $this->mysqlClientWriter->prepare($statement);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();

        if ($statement->rowCount() === 0) {
            return null;
        }

        $supermindRequest = SupermindRequest::fromData(
            $statement->fetch(PDO::FETCH_ASSOC)
        );

        return $supermindRequest;
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
            'status' => SupermindRequestStatus::CREATED->value,
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
     * @param int $replyActivityGuid
     * @return bool
     */
    public function updateSupermindRequestReplyActivityGuid(string $supermindRequestId, int $replyActivityGuid): bool
    {
        $statement = "UPDATE superminds SET reply_activity_guid = :reply_activity_guid, updated_timestamp = :update_timestamp WHERE guid = :guid";
        $values = [
            'reply_activity_guid' => $replyActivityGuid,
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
     * @return iterable<SupermindRequest>
     */
    public function getExpiredRequests(int $thresholdInSeconds): iterable
    {
        $statement = $this->mysqlClientReaderHandler->select()
            ->from('superminds')
            ->where('status', Operator::EQ, SupermindRequestStatus::CREATED->value)
            ->where('created_timestamp', Operator::LTE, date('c', strtotime("-${thresholdInSeconds} seconds")))
            ->prepare();

        $statement->execute();

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            yield SupermindRequest::fromData($row);
        }
    }

    /**
     * Get all requests where createTime is between min and max
     * @param int $gt
     * @param int $lt
     * @return Iterator
     */
    public function getRequestsExpiringSoon(int $gt, int $lt): Iterator
    {
        // BUILD STATEMENT

        $query = "SELECT * FROM superminds WHERE status = :status AND  created_timestamp > :min_timestamp AND created_timestamp < :max_timestamp ORDER BY created_timestamp DESC";
        $values = [
            'status' => SupermindRequestStatus::CREATED->value,
            'min_timestamp' => date('c', $gt),
            'max_timestamp' => date('c', $lt),
        ];

        $statement = $this->mysqlClientReader->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        // RUN STATEMENT

        $statement->execute();

        $i = 0;
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $request = SupermindRequest::fromData($row);
            $request->setEntity($this->entitiesBuilder->single($request->getActivityGuid()));
            $request->setReceiverEntity($this->entitiesBuilder->single($request->getReceiverGuid()));
            yield $request;
            $i++;
        }
    }

    /**
     * @param array $supermindRequestIds
     * @return SupermindRequest[]
     */
    public function getRequestsFromIds(array $supermindRequestIds): Iterator
    {
        $values = [];
        $query = "SELECT * FROM superminds WHERE guid in (";
        $query .= join(
            ",",
            array_map(
                function (int $index, string $value) use (&$values): string {
                    $values["supermind_$index"] = $value;
                    return ":supermind_$index";
                },
                array_keys($supermindRequestIds),
                array_values($supermindRequestIds)
            )
        );
        $query .= ")";

        $statement = $this->mysqlClientReader->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $request = SupermindRequest::fromData($row);
            yield $request;
        }
    }

    /**
     * @param int $status
     * @return SupermindRequest[]
     */
    public function getRequestsByStatus(int $status): Iterator
    {
        $query = 'SELECT * FROM superminds WHERE status = :status';
        $values = [
            'status' => $status
        ];
        $statement = $this->mysqlClientReader->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);
        $statement->execute();

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            yield SupermindRequest::fromData($row);
        }
    }

    public function saveSupermindRefundTransaction(string $supermindRequestId, string $transactionId): void
    {
        $query = "INSERT INTO supermind_refunds (supermind_request_guid, tx_id) VALUES (?, ?)";
        $values = [
            $supermindRequestId,
            $transactionId
        ];
        $statement = $this->mysqlClientWriter->prepare($query);
        $result = $statement->execute($values);

        if (!$result) {
            throw new PDOException("An error occurred whilst storing the details for the Supermind payment refund");
        }
    }

    public function getSupermindRefundTransactionId(string $supermindRequestId): string|false
    {
        $query = "SELECT tx_id FROM supermind_refunds WHERE supermind_request_guid = ?";
        $statement = $this->mysqlClientReader->prepare($query);
        $result = $statement->execute([$supermindRequestId]);

        if (!$result || $statement->rowCount() === 0) {
            return false;
        }

        return $statement->fetch(PDO::FETCH_ASSOC)['tx_id'];
    }
}
