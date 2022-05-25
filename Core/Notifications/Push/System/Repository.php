<?php

namespace Minds\Core\Notifications\Push\System;

use Cassandra\Bigint;
use Cassandra\Timestamp;
use Cassandra\Timeuuid;
use Minds\Core\Data\Cassandra\Client as CassandraClient;
use Minds\Core\Data\Cassandra\Prepared\Custom as PreparedStatement;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Core\Notifications\Push\System\Models\AdminPushNotificationRequest;
use Minds\Core\Notifications\Push\System\Models\AdminPushNotificationRequestCounters;
use Minds\Core\Notifications\Push\UndeliverableException;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

/**
 *
 */
class Repository
{
    protected User $user;
    private Logger $logger;

    public function __construct(
        protected ?CassandraClient $cassandraClient = null
    ) {
        $this->cassandraClient ??= Di::_()->get('Database\Cassandra\Cql');
        $this->logger = Di::_()->get("Logger");
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function add(
        AdminPushNotificationRequest $notification
    ): void {
        $query = $this->buildAddQuery($notification);
        $this->cassandraClient->request($query);
    }

    private function buildAddQuery(AdminPushNotificationRequest $notification): PreparedStatement
    {
        $query = new PreparedStatement();
        $requestId = new Timeuuid(time());
        $notification->setRequestUuid($requestId->uuid());
        return $query->query(
            "INSERT INTO
                system_push_notifications
                (type, request_uuid, author_guid, created_at, title, message, url, target, counter, status)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?);",
            [
                $notification->getType(),
                $requestId,
                new Bigint($this->user->getGuid()),
                new Timestamp(time(), 0),
                $notification->getTitle(),
                $notification->getMessage(),
                $notification->getLink(),
                $notification->getTarget(),
                $notification->getCounter(),
                AdminPushNotificationRequestStatus::PENDING
            ]
        );
    }

    /**
     * @return AdminPushNotificationRequest[]
     * @throws ServerErrorException
     */
    public function getCompletedRequests(): array
    {
        $query = $this->buildFetchCompletedRequests();
        $notifications = [];

        foreach ($this->cassandraClient->request($query) as $notification) {
            $notifications[] = AdminPushNotificationRequest::fromArray($notification);
        }

        return $notifications;
    }

    private function buildFetchCompletedRequests(): PreparedStatement
    {
        return (new PreparedStatement())
            ->query(
                "SELECT *
                FROM
                    system_push_notifications
                LIMIT 12;"
            );
    }

    public function updateRequestStartedOnDate(string $type, string $requestUuid): void
    {
        $query = (new PreparedStatement())
            ->query(
                "UPDATE system_push_notifications SET started_at = ?, status = ? WHERE type = ? AND request_uuid = ?;",
                [
                    new Timestamp(time(), 0),
                    AdminPushNotificationRequestStatus::IN_PROGRESS,
                    $type,
                    new Timeuuid($requestUuid)
                ]
            );

        $this->cassandraClient->request($query);
    }

    public function updateRequestCompletedOnDate(string $type, string $requestUuid, int $status): void
    {
        $query = (new PreparedStatement())
            ->query(
                "UPDATE system_push_notifications SET completed_at = ?, status = ? WHERE type = ? AND request_uuid = ?;",
                [
                    new Timestamp(time(), 0),
                    $status,
                    $type,
                    new Timeuuid($requestUuid)
                ]
            );

        $this->cassandraClient->request($query);
    }

    /**
     * @param string $type
     * @param string $requestUuid
     * @param AdminPushNotificationRequestCounters $requestCounters
     * @return void
     */
    public function updateRequestCounters(
        string $type,
        string $requestUuid,
        AdminPushNotificationRequestCounters $requestCounters
    ): void {
        $query = (new PreparedStatement())
            ->query(
                "UPDATE system_push_notifications SET counter = ?, successful_counter = ?, failed_counter = ?, skipped_counter = ? WHERE type = ? AND request_uuid = ?;",
                [
                    $requestCounters->getTotalNotifications(),
                    $requestCounters->getSuccessfulNotifications(),
                    $requestCounters->getFailedNotifications(),
                    $requestCounters->getSkippedNotifications(),
                    $type,
                    new Timeuuid($requestUuid)
                ]
            );

        $this->cassandraClient->request($query);
    }

    /**
     * @param string $type
     * @param string $requestUuid
     * @return AdminPushNotificationRequest
     * @throws ServerErrorException
     * @throws UndeliverableException
     */
    public function getByRequestId(string $type, string $requestUuid): AdminPushNotificationRequest
    {
        $query = (new PreparedStatement())
            ->query(
                "SELECT
                        *
                    FROM
                        system_push_notifications
                    WHERE
                        type = ? AND request_uuid = ? AND status = ?
                    LIMIT 1;",
                [
                    $type,
                    new Timeuuid($requestUuid),
                    AdminPushNotificationRequestStatus::PENDING
                ]
            );

        $this->logger->addWarning("Fetching request by Uuid: " . $requestUuid);

        $rows = $this->cassandraClient->request($query);

        if (!$rows || $rows->count() == 0) {
            throw new UndeliverableException("No request was found");
        }

        $row = $rows->first();
        return AdminPushNotificationRequest::fromArray($row);
    }
}
