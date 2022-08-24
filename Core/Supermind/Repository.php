<?php

namespace Minds\Core\Supermind;

use Iterator;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Di\Di;
use Minds\Core\Supermind\Models\SupermindRequest;
use PDO;
use PDOException;
use PDOStatement;

class Repository
{
    private PDO $mysqlClient;

    public function __construct(
        private ?MySQLClient $mysqlHandler = null
    ) {
        $this->mysqlHandler ??= Di::_()->get("Database\MySQL\Client");
        $this->mysqlClient = $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_REPLICA);
    }

    public function beginTransaction(): void
    {
        if ($this->mysqlClient->inTransaction()) {
            throw new PDOException("Cannot initiate transaction. Previously initiated transaction still in progress.");
        }

        $this->mysqlClient->beginTransaction();
    }

    public function rollbackTransaction(): void
    {
        $this->mysqlClient->rollBack();
    }

    public function commitTransaction(): void
    {
        $this->mysqlClient->commit();
    }

    /**
     * @param string $receiverGuid
     * @return Iterator
     */
    public function getReceivedRequests(string $receiverGuid): Iterator
    {
        $statement = $this->buildReceivedRequestsQuery($receiverGuid);
        $statement->execute();

        foreach ($statement as $row) {
            yield SupermindRequest::fromData($row);
        }
    }

    /**
     * @param string $receiverGuid
     * @return PDOStatement
     */
    private function buildReceivedRequestsQuery(string $receiverGuid): PDOStatement
    {
        $query = "SELECT
                *
            FROM
                superminds
            WHERE
                receiver_guid = :receiver_guid and status != ?
            ORDER BY
                status, created_timestamp DESC";
        $values = [
            'receiver_guid' => $receiverGuid
        ];

        $statement = $this->mysqlClient->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);
        return $statement;
    }

    /**
     * @param string $senderGuid
     * @return Iterator
     */
    public function getSentRequests(string $senderGuid): Iterator
    {
        $statement = $this->buildSentRequestsQuery($senderGuid);
        $statement->execute();

        foreach ($statement as $row) {
            yield SupermindRequest::fromData($row);
        }
    }

    private function buildSentRequestsQuery(string $senderGuid): PDOStatement
    {
        $query = "SELECT
                *
            FROM
                superminds
            WHERE
                sender_guid = :sender_guid";
        $values = [
            'sender_guid' => $senderGuid
        ];

        $statement = $this->mysqlClient->prepare($query);
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
                superminds (guid, sender_guid, receiver_guid, status, payment_amount, payment_method, payment_txid, created_timestamp, twitter_required, reply_type)
            VALUES
                (:guid, :sender_guid, :receiver_guid, :status, :payment_amount, :payment_method, :payment_txid, :created_timestamp :twitter_required, :reply_type)";
        $values = [
            "guid" => $request->getGuid(),
            "sender_guid" => $request->getSenderGuid(),
            "receiver_guid" => $request->getReceiverGuid(),
            "status" => SupermindRequestStatus::CREATED,
            "payment_amount" => $request->getPaymentAmount(),
            "payment_method" => $request->getPaymentMethod(),
            "payment_txid" => $request->getPaymentTxID(),
            "created_timestamp" => time(),
            "twitter_required" => $request->getTwitterRequired(),
            "reply_type" => $request->getReplyType()
        ];

        $statement = $this->mysqlClient->prepare($query);
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
            "updated_timestamp" => time(),
            "guid" => $supermindRequestId
        ];

        $statement = $this->mysqlClient->prepare($statement);
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

        $statement = $this->mysqlClient->prepare($statement);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();

        if ($statement->rowCount() === 0) {
            return null;
        }

        return SupermindRequest::fromData(
            $statement->fetch(PDO::FETCH_ASSOC)
        );
    }
}
