<?php

namespace Spec\Minds\Core\Supermind;

use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Supermind\Repository;
use Minds\Core\Supermind\SupermindRequestPaymentMethod;
use Minds\Core\Supermind\SupermindRequestReplyType;
use Minds\Core\Supermind\SupermindRequestStatus;
use PDO;
use PDOException;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Spec\Minds\Common\Traits\CommonMatchers;

class RepositorySpec extends ObjectBehavior
{
    use CommonMatchers;

    /** @var MySQLClient */
    private Collaborator $mysqlHandler;

    /** @var EntitiesBuilder */
    private Collaborator $entitiesBuilder;

    /** @var PDO */
    private Collaborator $mysqlClientReader;

    /** @var PDO */
    private Collaborator $mysqlClientWriter;

    private Collaborator $loggerMock;

    public function let(
        MySQLClient $mysqlHandler,
        Logger $logger,
        EntitiesBuilder $entitiesBuilder,
        PDO $mysqlClientReader,
        PDO $mysqlClientWriter
    ) {
        $mysqlHandler->getConnection(MySQLClient::CONNECTION_REPLICA)
            ->shouldBeCalled()
            ->willReturn($mysqlClientReader);

        $mysqlHandler->getConnection(MySQLClient::CONNECTION_MASTER)
            ->shouldBeCalled()
            ->willReturn($mysqlClientWriter);



        $this->entitiesBuilder = $entitiesBuilder;
        $this->mysqlHandler = $mysqlHandler;
        $this->mysqlClientReader = $mysqlClientReader;
        $this->mysqlClientWriter = $mysqlClientWriter;
        $this->loggerMock = $logger;

        $this->beConstructedWith(
            $this->mysqlHandler,
            $this->loggerMock,
            $entitiesBuilder
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    // beginTransaction

    public function it_should_begin_a_transaction()
    {
        $this->mysqlClientWriter->inTransaction()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->mysqlClientWriter->beginTransaction()
            ->shouldBeCalled();

        $this->beginTransaction();
    }

    public function it_should_not_begin_a_transaction_if_in_transaction_already()
    {
        $this->mysqlClientWriter->inTransaction()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->shouldThrow(PDOException::class)->duringBeginTransaction();
    }

    // rollbackTransaction

    public function it_should_rollback_a_transaction()
    {
        $this->mysqlClientWriter->inTransaction()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->mysqlClientWriter->rollBack()
            ->shouldBeCalled();

        $this->rollbackTransaction();
    }

    public function it_should_not_rollback_a_transaction_if_not_in_transaction()
    {
        $this->mysqlClientWriter->inTransaction()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->mysqlClientWriter->rollBack()
            ->shouldNotBeCalled();

        $this->rollbackTransaction();
    }

    // commitTransaction

    public function it_should_commit_a_transaction()
    {
        $this->mysqlClientWriter->commit()
            ->shouldBeCalled();

        $this->commitTransaction();
    }

    // getReceivedRequests

    public function it_should_get_received_requests(
        PDOStatement $pdoStatement,
    ) {
        $receiverGuid = '567';
        $status = null;
        $receiverGuid = '123';
        $offset = 12;
        $limit = 24;

        $pdoStatement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatement->fetchAll(PDO::FETCH_ASSOC)
            ->shouldBeCalled()
            ->willReturn([]);

        $this->mysqlClientReader->prepare(Argument::that(function ($arg) {
            return $this->forceStringSingleLine($arg) === $this->forceStringSingleLine(
                "SELECT * FROM superminds
                WHERE receiver_guid = :receiver_guid AND status != :excludedStatus
                ORDER BY created_timestamp DESC
                LIMIT :offset, :limit"
            );
        }))
            ->shouldBeCalled()
            ->willReturn($pdoStatement);

        $this->mysqlHandler->bindValuesToPreparedStatement($pdoStatement, Argument::that(function ($arg) use ($receiverGuid) {
            return $arg['receiver_guid'] === $receiverGuid &&
                $arg['offset'] === 12 &&
                $arg['limit'] === 24;
        }))->shouldBeCalled();

        $this->getReceivedRequests($receiverGuid, $offset, $limit, $status)->shouldBeAGenerator([]);
    }

    public function it_should_get_received_requests_for_a_specific_status(
        PDOStatement $pdoStatement,
    ) {
        $receiverGuid = '567';
        $status = SupermindRequestStatus::from(3);
        $receiverGuid = '123';
        $offset = 12;
        $limit = 24;

        $pdoStatement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatement->fetchAll(PDO::FETCH_ASSOC)
            ->shouldBeCalled()
            ->willReturn([]);

        $this->mysqlClientReader->prepare(Argument::that(function ($arg) {
            return $this->forceStringSingleLine($arg) === $this->forceStringSingleLine(
                "SELECT * FROM superminds
                WHERE receiver_guid = :receiver_guid AND status != :excludedStatus AND status = :status
                ORDER BY created_timestamp DESC
                LIMIT :offset, :limit"
            );
        }))
            ->shouldBeCalled()
            ->willReturn($pdoStatement);

        $this->mysqlHandler->bindValuesToPreparedStatement($pdoStatement, Argument::that(function ($arg) use ($receiverGuid, $status) {
            return $arg['receiver_guid'] === $receiverGuid &&
                $arg['offset'] === 12 &&
                $arg['limit'] === 24 &&
                $arg['status'] === $status->value;
        }))->shouldBeCalled();

        $this->getReceivedRequests($receiverGuid, $offset, $limit, $status)->shouldBeAGenerator([]);
    }


    public function it_should_exclude_expired_superminds_when_getting_received_requests_for_created_status(
        PDOStatement $pdoStatement,
    ) {
        $receiverGuid = '567';
        $status = SupermindRequestStatus::from(1); // CREATED
        $receiverGuid = '123';
        $offset = 12;
        $limit = 24;

        $pdoStatement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatement->fetchAll(PDO::FETCH_ASSOC)
            ->shouldBeCalled()
            ->willReturn([]);

        $this->mysqlClientReader->prepare(Argument::that(function ($arg) {
            return $this->forceStringSingleLine($arg) === $this->forceStringSingleLine(
                "SELECT * FROM superminds
                WHERE receiver_guid = :receiver_guid AND status != :excludedStatus AND status = :status AND created_timestamp >= :min_timestamp
                ORDER BY created_timestamp DESC
                LIMIT :offset, :limit"
            );
        }))
            ->shouldBeCalled()
            ->willReturn($pdoStatement);

        $this->mysqlHandler->bindValuesToPreparedStatement($pdoStatement, Argument::that(function ($arg) use ($receiverGuid, $status) {
            return $arg['receiver_guid'] === $receiverGuid &&
                $arg['offset'] === 12 &&
                $arg['limit'] === 24 &&
                $arg['status'] === $status->value;
        }))->shouldBeCalled();

        $this->getReceivedRequests($receiverGuid, $offset, $limit, $status)->shouldBeAGenerator([]);
    }

    // getSentRequests

    public function it_should_get_sent_requests(
        PDOStatement $pdoStatement,
    ) {
        $status = null;
        $senderGuid = '123';
        $offset = 12;
        $limit = 24;

        $pdoStatement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatement->fetchAll(PDO::FETCH_ASSOC)
            ->shouldBeCalled()
            ->willReturn([]);

        $this->mysqlClientReader->prepare(Argument::that(function ($arg) {
            return $this->forceStringSingleLine($arg) === $this->forceStringSingleLine(
                "SELECT * FROM superminds
                WHERE sender_guid = :sender_guid AND status != :excludedStatus
                ORDER BY created_timestamp DESC
                LIMIT :offset, :limit"
            );
        }))
            ->shouldBeCalled()
            ->willReturn($pdoStatement);

        $this->mysqlHandler->bindValuesToPreparedStatement($pdoStatement, Argument::that(function ($arg) use ($senderGuid) {
            return $arg['sender_guid'] === $senderGuid &&
                $arg['offset'] === 12 &&
                $arg['limit'] === 24;
        }))->shouldBeCalled();

        $this->getSentRequests($senderGuid, $offset, $limit, $status)->shouldBeAGenerator([]);
    }

    public function it_should_get_sent_requests_for_a_specific_status(
        PDOStatement $pdoStatement,
    ) {
        $status = SupermindRequestStatus::from(3);
        $senderGuid = '123';
        $offset = 12;
        $limit = 24;

        $pdoStatement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatement->fetchAll(PDO::FETCH_ASSOC)
            ->shouldBeCalled()
            ->willReturn([]);

        $this->mysqlClientReader->prepare(Argument::that(function ($arg) {
            return $this->forceStringSingleLine($arg) === $this->forceStringSingleLine(
                "SELECT * FROM superminds
                WHERE sender_guid = :sender_guid AND status != :excludedStatus AND status = :status
                ORDER BY created_timestamp DESC
                LIMIT :offset, :limit"
            );
        }))
            ->shouldBeCalled()
            ->willReturn($pdoStatement);

        $this->mysqlHandler->bindValuesToPreparedStatement($pdoStatement, Argument::that(function ($arg) use ($senderGuid, $status) {
            return $arg['sender_guid'] === $senderGuid &&
                $arg['offset'] === 12 &&
                $arg['limit'] === 24 &&
                $arg['status'] === $status->value;
        }))->shouldBeCalled();

        $this->getSentRequests($senderGuid, $offset, $limit, $status)->shouldBeAGenerator([]);
    }

    public function it_should_exclude_expired_superminds_when_getting_sent_requests_for_created_status(
        PDOStatement $pdoStatement,
    ) {
        $status = SupermindRequestStatus::from(1); // CREATED
        $senderGuid = '123';
        $offset = 12;
        $limit = 24;

        $pdoStatement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatement->fetchAll(PDO::FETCH_ASSOC)
            ->shouldBeCalled()
            ->willReturn([]);

        $this->mysqlClientReader->prepare(Argument::that(function ($arg) {
            return $this->forceStringSingleLine($arg) === $this->forceStringSingleLine(
                "SELECT * FROM superminds
                WHERE sender_guid = :sender_guid AND status != :excludedStatus AND status = :status AND created_timestamp >= :min_timestamp
                ORDER BY created_timestamp DESC
                LIMIT :offset, :limit"
            );
        }))
            ->shouldBeCalled()
            ->willReturn($pdoStatement);

        $this->mysqlHandler->bindValuesToPreparedStatement($pdoStatement, Argument::that(function ($arg) use ($senderGuid, $status) {
            return $arg['sender_guid'] === $senderGuid &&
                $arg['offset'] === 12 &&
                $arg['limit'] === 24 &&
                $arg['status'] === $status->value;
        }))->shouldBeCalled();

        $this->getSentRequests($senderGuid, $offset, $limit, $status)->shouldBeAGenerator([]);
    }

    // countReceivedRequests

    public function it_should_count_received_requests(
        PDOStatement $pdoStatement,
    ) {
        $receiverGuid = '567';
        $status = null;
        $receiverGuid = '123';
        $resultCount = 3;
        $pdoStatement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatement->fetch(PDO::FETCH_ASSOC)
            ->shouldBeCalled()
            ->willReturn([
                'count' => $resultCount
            ]);

        $this->mysqlClientReader->prepare(Argument::that(function ($arg) {
            return $this->forceStringSingleLine($arg) === $this->forceStringSingleLine("
                SELECT COUNT(*) as count FROM superminds
                WHERE receiver_guid = :receiver_guid AND status != :excludedStatus
            ");
        }))
            ->shouldBeCalled()
            ->willReturn($pdoStatement);

        $this->mysqlHandler->bindValuesToPreparedStatement($pdoStatement, Argument::that(function ($arg) use ($receiverGuid) {
            return $arg['receiver_guid'] === $receiverGuid;
        }))->shouldBeCalled();

        $this->countReceivedRequests($receiverGuid, $status)->shouldBe($resultCount);
    }

    public function it_should_count_received_requests_for_a_given_status(
        PDOStatement $pdoStatement,
    ) {
        $receiverGuid = '567';
        $status = SupermindRequestStatus::from(3);
        $receiverGuid = '123';
        $resultCount = 3;

        $pdoStatement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatement->fetch(PDO::FETCH_ASSOC)
            ->shouldBeCalled()
            ->willReturn([
                'count' => $resultCount
            ]);

        $this->mysqlClientReader->prepare(Argument::that(function ($arg) {
            return $this->forceStringSingleLine($arg) === $this->forceStringSingleLine("
                SELECT COUNT(*) as count FROM superminds
                WHERE receiver_guid = :receiver_guid AND status != :excludedStatus AND status = :status
            ");
        }))
            ->shouldBeCalled()
            ->willReturn($pdoStatement);

        $this->mysqlHandler->bindValuesToPreparedStatement($pdoStatement, Argument::that(function ($arg) use ($receiverGuid, $status) {
            return $arg['receiver_guid'] === $receiverGuid  &&
            $arg['status'] === $status->value;
        }))->shouldBeCalled();

        $this->countReceivedRequests($receiverGuid, $status)->shouldBe($resultCount);
    }

    public function it_should_count_received_requests_filtering_out_unmarked_expired_for_created_superminds(
        PDOStatement $pdoStatement,
    ) {
        $receiverGuid = '567';
        $status = SupermindRequestStatus::CREATED;
        $receiverGuid = '123';
        $resultCount = 3;

        $pdoStatement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatement->fetch(PDO::FETCH_ASSOC)
            ->shouldBeCalled()
            ->willReturn([
                'count' => $resultCount
            ]);

        $this->mysqlClientReader->prepare(Argument::that(function ($arg) {
            return $this->forceStringSingleLine($arg) === $this->forceStringSingleLine("
                SELECT COUNT(*) as count
                FROM superminds
                WHERE receiver_guid = :receiver_guid
                AND status != :excludedStatus
                AND status = :status
                AND created_timestamp >= :min_timestamp
            ");
        }))
            ->shouldBeCalled()
            ->willReturn($pdoStatement);

        $this->mysqlHandler->bindValuesToPreparedStatement($pdoStatement, Argument::that(function ($arg) use ($receiverGuid) {
            return $arg['receiver_guid'] === $receiverGuid;
        }))->shouldBeCalled();

        $this->countReceivedRequests($receiverGuid, $status)->shouldBe($resultCount);
    }

    // countSentRequests

    public function it_should_count_sent_requests(
        PDOStatement $pdoStatement,
    ) {
        $senderGuid = '567';
        $status = null;
        $resultCount = 3;

        $pdoStatement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatement->fetch(PDO::FETCH_ASSOC)
            ->shouldBeCalled()
            ->willReturn([
                'count' => $resultCount
            ]);

        $this->mysqlClientReader->prepare(Argument::that(function ($arg) {
            return $this->forceStringSingleLine($arg) === $this->forceStringSingleLine("
                SELECT COUNT(*) as count FROM superminds
                WHERE sender_guid = :sender_guid AND status != :excludedStatus
            ");
        }))
            ->shouldBeCalled()
            ->willReturn($pdoStatement);

        $this->mysqlHandler->bindValuesToPreparedStatement($pdoStatement, Argument::that(function ($arg) use ($senderGuid) {
            return $arg['sender_guid'] === $senderGuid;
        }))->shouldBeCalled();

        $this->countSentRequests($senderGuid, $status)->shouldBe($resultCount);
    }

    public function it_should_count_sent_requests_for_a_given_status(
        PDOStatement $pdoStatement,
    ) {
        $senderGuid = '567';
        $status = SupermindRequestStatus::from(3);
        $resultCount = 3;

        $pdoStatement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatement->fetch(PDO::FETCH_ASSOC)
            ->shouldBeCalled()
            ->willReturn([
                'count' => $resultCount
            ]);

        $this->mysqlClientReader->prepare(Argument::that(function ($arg) {
            return $this->forceStringSingleLine($arg) === $this->forceStringSingleLine("
                SELECT COUNT(*) as count FROM superminds
                WHERE sender_guid = :sender_guid AND status != :excludedStatus AND status = :status
            ");
        }))
            ->shouldBeCalled()
            ->willReturn($pdoStatement);

        $this->mysqlHandler->bindValuesToPreparedStatement($pdoStatement, Argument::that(function ($arg) use ($senderGuid, $status) {
            return $arg['sender_guid'] === $senderGuid &&
                $arg['status'] === $status->value;
        }))->shouldBeCalled();

        $this->countSentRequests($senderGuid, $status)->shouldBe($resultCount);
    }

    // addSupermindRequest

    public function it_should_add_a_supermind_request(
        PDOStatement $pdoStatement,
        SupermindRequest $supermindRequest
    ) {
        $guid = '123';
        $senderGuid = '234';
        $receiverGuid = '345';
        $paymentAmount = 300;
        $paymentMethod = SupermindRequestPaymentMethod::CASH;
        $paymentReference = 'pay_123';
        $twitterRequired = 1;
        $replyType = SupermindRequestReplyType::IMAGE;

        $supermindRequest->getGuid()
            ->shouldBeCalled()
            ->willReturn($guid);

        $supermindRequest->getSenderGuid()
            ->shouldBeCalled()
            ->willReturn($senderGuid);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $supermindRequest->getPaymentAmount()
            ->shouldBeCalled()
            ->willReturn($paymentAmount);

        $supermindRequest->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $supermindRequest->getPaymentTxID()
            ->shouldBeCalled()
            ->willReturn($paymentReference);

        $supermindRequest->getTwitterRequired()
            ->shouldBeCalled()
            ->willReturn($twitterRequired);

        $supermindRequest->getReplyType()
            ->shouldBeCalled()
            ->willReturn($replyType);

        $pdoStatement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->mysqlClientWriter->prepare(Argument::that(function ($arg) {
            return $this->forceStringSingleLine($arg) === $this->forceStringSingleLine("
                INSERT INTO
                    superminds (guid, sender_guid, receiver_guid, status, payment_amount, payment_method, payment_reference, created_timestamp, twitter_required, reply_type)
                VALUES
                    (:guid, :sender_guid, :receiver_guid, :status, :payment_amount, :payment_method, :payment_reference, :created_timestamp, :twitter_required, :reply_type)
            ");
        }))
            ->shouldBeCalled()
            ->willReturn($pdoStatement);

        $this->mysqlHandler->bindValuesToPreparedStatement($pdoStatement, Argument::that(function ($arg) use (
            $guid,
            $senderGuid,
            $receiverGuid,
            $paymentAmount,
            $paymentMethod,
            $paymentReference,
            $twitterRequired,
            $replyType
        ) {
            return $arg['guid'] === $guid &&
                $arg['sender_guid'] === $senderGuid &&
                $arg['receiver_guid'] === $receiverGuid &&
                $arg['status'] === SupermindRequestStatus::PENDING->value &&
                $arg['payment_amount'] === $paymentAmount &&
                $arg['payment_method']=== $paymentMethod &&
                $arg['payment_reference'] === $paymentReference &&
                is_string($arg['created_timestamp']) &&
                $arg['twitter_required'] === $twitterRequired &&
                $arg['reply_type'] === $replyType;
        }))->shouldBeCalled();

        $this->addSupermindRequest($supermindRequest)->shouldBe(true);
    }

    // updateSupermindRequestStatus

    public function it_should_update_a_supermind_request(PDOStatement $pdoStatement)
    {
        $status = SupermindRequestStatus::CREATED;
        $supermindRequestId = '123';

        $pdoStatement->execute()
            ->willReturn(true);

        $this->mysqlClientWriter->prepare(Argument::that(function ($arg) {
            return $this->forceStringSingleLine($arg) === $this->forceStringSingleLine("
                UPDATE superminds SET status = :status, updated_timestamp = :updated_timestamp WHERE guid = :guid
            ");
        }))
            ->shouldBeCalled()
            ->willReturn($pdoStatement);

        $this->mysqlHandler->bindValuesToPreparedStatement($pdoStatement, Argument::that(function ($arg) use ($supermindRequestId, $status) {
            return $arg['status'] === $status->value &&
                $arg['guid'] === $supermindRequestId;
        }))->shouldBeCalled();

        $this->updateSupermindRequestStatus($status, $supermindRequestId);
    }

    // getSupermindRequest

    public function it_should_get_a_supermind_request(
        PDOStatement $pdoStatement
    ) {
        $supermindRequestId = '123';

        $this->mysqlClientWriter->prepare(Argument::that(function ($arg) {
            return $this->forceStringSingleLine($arg) === $this->forceStringSingleLine("
                SELECT * FROM superminds WHERE guid = :guid
            ");
        }))
            ->shouldBeCalled()
            ->willReturn($pdoStatement);

        $this->mysqlHandler->bindValuesToPreparedStatement($pdoStatement, Argument::that(function ($arg) use ($supermindRequestId) {
            return $arg['guid'] === $supermindRequestId;
        }))->shouldBeCalled();

        $pdoStatement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatement->rowCount()
            ->shouldBeCalled()
            ->willReturn(1);

        $pdoStatement->fetch(PDO::FETCH_ASSOC)
            ->shouldBeCalled()
            ->willReturn([]);

        $this->getSupermindRequest($supermindRequestId);
    }

    public function it_should_return_null_when_getting_a_supermind_request_if_no_rows_are_found(
        PDOStatement $pdoStatement
    ) {
        $supermindRequestId = '123';

        $this->mysqlClientWriter->prepare(Argument::that(function ($arg) {
            return $this->forceStringSingleLine($arg) === $this->forceStringSingleLine("
                SELECT * FROM superminds WHERE guid = :guid
            ");
        }))
            ->shouldBeCalled()
            ->willReturn($pdoStatement);

        $this->mysqlHandler->bindValuesToPreparedStatement($pdoStatement, Argument::that(function ($arg) use ($supermindRequestId) {
            return $arg['guid'] === $supermindRequestId;
        }))->shouldBeCalled();

        $pdoStatement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatement->rowCount()
            ->shouldBeCalled()
            ->willReturn(0);

        $this->getSupermindRequest($supermindRequestId)->shouldBe(null);
    }

    // updateSupermindRequestActivityGuid

    public function it_should_update_a_supermind_request_activity_guid(
        PDOStatement $pdoStatement
    ) {
        $supermindRequestId = '123';
        $activityGuid = '234';

        $this->mysqlClientWriter->prepare(Argument::that(function ($arg) {
            return $this->forceStringSingleLine($arg) === $this->forceStringSingleLine("
                UPDATE superminds SET activity_guid = :activity_guid, status = :status, updated_timestamp = :update_timestamp WHERE guid = :guid
            ");
        }))
            ->shouldBeCalled()
            ->willReturn($pdoStatement);

        $this->mysqlHandler->bindValuesToPreparedStatement($pdoStatement, Argument::that(function ($arg) use (
            $supermindRequestId,
            $activityGuid
        ) {
            return $arg['guid'] === $supermindRequestId &&
                $arg['activity_guid'] === (int) $activityGuid &&
                is_string($arg['update_timestamp']) &&
                $arg['status'] === SupermindRequestStatus::CREATED->value;
        }))->shouldBeCalled();

        $pdoStatement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatement->rowCount()
            ->shouldBeCalled()
            ->willReturn(1);

        $this->updateSupermindRequestActivityGuid($supermindRequestId, $activityGuid)->shouldBe(true);
    }

    public function it_should_return_false_on_update_a_supermind_request_activity_guid_when_no_rows_are_found(
        PDOStatement $pdoStatement
    ) {
        $supermindRequestId = '123';
        $activityGuid = '234';

        $this->mysqlClientWriter->prepare(Argument::that(function ($arg) {
            return $this->forceStringSingleLine($arg) === $this->forceStringSingleLine("
                UPDATE superminds SET activity_guid = :activity_guid, status = :status, updated_timestamp = :update_timestamp WHERE guid = :guid
            ");
        }))
            ->shouldBeCalled()
            ->willReturn($pdoStatement);

        $this->mysqlHandler->bindValuesToPreparedStatement($pdoStatement, Argument::that(function ($arg) use (
            $supermindRequestId,
            $activityGuid
        ) {
            return $arg['guid'] === $supermindRequestId &&
                $arg['activity_guid'] === (int) $activityGuid &&
                is_string($arg['update_timestamp']) &&
                $arg['status'] === SupermindRequestStatus::CREATED->value;
        }))->shouldBeCalled();

        $pdoStatement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatement->rowCount()
            ->shouldBeCalled()
            ->willReturn(0);

        $this->updateSupermindRequestActivityGuid($supermindRequestId, $activityGuid)->shouldBe(false);
    }

    // updateSupermindRequestReplyActivityGuid

    public function it_should_update_a_supermind_request_reply_activity_guid(
        PDOStatement $pdoStatement
    ) {
        $supermindRequestId = '123';
        $activityGuid = '234';

        $this->mysqlClientWriter->prepare(Argument::that(function ($arg) {
            return $this->forceStringSingleLine($arg) === $this->forceStringSingleLine("
                UPDATE superminds SET reply_activity_guid = :reply_activity_guid, updated_timestamp = :update_timestamp WHERE guid = :guid
            ");
        }))
            ->shouldBeCalled()
            ->willReturn($pdoStatement);

        $this->mysqlHandler->bindValuesToPreparedStatement($pdoStatement, Argument::that(function ($arg) use (
            $supermindRequestId,
            $activityGuid
        ) {
            return $arg['guid'] === $supermindRequestId &&
                $arg['reply_activity_guid'] === (int) $activityGuid &&
                is_string($arg['update_timestamp']);
        }))->shouldBeCalled();

        $pdoStatement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatement->rowCount()
            ->shouldBeCalled()
            ->willReturn(1);

        $this->updateSupermindRequestReplyActivityGuid($supermindRequestId, $activityGuid)->shouldBe(true);
    }

    public function it_should_return_false_on_update_a_supermind_request_reply_activity_guid_when_no_rows_are_found(
        PDOStatement $pdoStatement
    ) {
        $supermindRequestId = '123';
        $activityGuid = '234';

        $this->mysqlClientWriter->prepare(Argument::that(function ($arg) {
            return $this->forceStringSingleLine($arg) === $this->forceStringSingleLine("
                UPDATE superminds SET reply_activity_guid = :reply_activity_guid, updated_timestamp = :update_timestamp WHERE guid = :guid
            ");
        }))
            ->shouldBeCalled()
            ->willReturn($pdoStatement);

        $this->mysqlHandler->bindValuesToPreparedStatement($pdoStatement, Argument::that(function ($arg) use (
            $supermindRequestId,
            $activityGuid
        ) {
            return $arg['guid'] === $supermindRequestId &&
                $arg['reply_activity_guid'] === (int) $activityGuid &&
                is_string($arg['update_timestamp']);
        }))->shouldBeCalled();

        $pdoStatement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatement->rowCount()
            ->shouldBeCalled()
            ->willReturn(0);

        $this->updateSupermindRequestReplyActivityGuid($supermindRequestId, $activityGuid)->shouldBe(false);
    }

    // deleteSupermindRequest

    public function it_should_delete_a_supermind_request(
        PDOStatement $pdoStatement
    ) {
        $supermindRequestId = '123';

        $this->mysqlClientWriter->prepare(Argument::that(function ($arg) {
            return $this->forceStringSingleLine($arg) === $this->forceStringSingleLine("
                DELETE FROM superminds WHERE guid = :guid
            ");
        }))
            ->shouldBeCalled()
            ->willReturn($pdoStatement);

        $this->mysqlHandler->bindValuesToPreparedStatement($pdoStatement, Argument::that(function ($arg) use (
            $supermindRequestId
        ) {
            return $arg['guid'] === $supermindRequestId;
        }))->shouldBeCalled();

        $pdoStatement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatement->rowCount()
            ->shouldBeCalled()
            ->willReturn(1);

        $this->deleteSupermindRequest($supermindRequestId)->shouldBe(true);
    }

    public function it_should_return_false_on_deleting_a_supermind_request_when_no_rows_are_found(
        PDOStatement $pdoStatement
    ) {
        $supermindRequestId = '123';

        $this->mysqlClientWriter->prepare(Argument::that(function ($arg) {
            return $this->forceStringSingleLine($arg) === $this->forceStringSingleLine("
                DELETE FROM superminds WHERE guid = :guid
            ");
        }))
            ->shouldBeCalled()
            ->willReturn($pdoStatement);

        $this->mysqlHandler->bindValuesToPreparedStatement($pdoStatement, Argument::that(function ($arg) use (
            $supermindRequestId
        ) {
            return $arg['guid'] === $supermindRequestId;
        }))->shouldBeCalled();

        $pdoStatement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatement->rowCount()
            ->shouldBeCalled()
            ->willReturn(0);

        $this->deleteSupermindRequest($supermindRequestId)->shouldBe(false);
    }

    // getRequestsExpiringSoon

    public function it_should_get_requests_expiring_soon(PDOStatement $pdoStatement)
    {
        $gt = 9;
        $lt = 10;

        $pdoStatement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatement->fetchAll(PDO::FETCH_ASSOC)
            ->shouldBeCalled()
            ->willReturn([]);

        $this->mysqlClientReader->prepare(Argument::that(function ($arg) {
            return $this->forceStringSingleLine($arg) === $this->forceStringSingleLine("
                SELECT * FROM superminds WHERE status = :status AND  created_timestamp > :min_timestamp AND created_timestamp < :max_timestamp ORDER BY created_timestamp DESC
            ");
        }))
            ->shouldBeCalled()
            ->willReturn($pdoStatement);

        $this->mysqlHandler->bindValuesToPreparedStatement($pdoStatement, Argument::that(function ($arg) {
            return $arg['status'] === SupermindRequestStatus::CREATED->value &&
                is_string($arg['min_timestamp']) &&
                is_string($arg['max_timestamp']);
        }))->shouldBeCalled();

        $this->getRequestsExpiringSoon($gt, $lt)->shouldBeAGenerator([]);
    }

    public function forceStringSingleLine(string $string)
    {
        return trim(preg_replace('/\s+/', ' ', str_replace(["\n", "\r"], '', $string)));
    }
}
