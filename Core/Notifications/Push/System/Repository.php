<?php

namespace Minds\Core\Notifications\Push\System;

use Cassandra\Bigint;
use Cassandra\Timestamp;
use Cassandra\Timeuuid;
use Minds\Core\Data\Cassandra\Client as CassandraClient;
use Minds\Core\Data\Cassandra\Prepared\Custom as PreparedStatement;
use Minds\Core\Di\Di;
use Minds\Core\Notifications\Push\System\Models\AdminPushNotificationRequest;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

/**
 *
 */
class Repository
{
    protected User $user;

    public function __construct(
        protected ?CassandraClient $cassandraClient = null
    ) {
        $this->cassandraClient ??= Di::_()->get('Database\Cassandra\Cql');
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
        $notification->setRequestId($requestId->uuid());
        return $query->query(
            "INSERT INTO
                system_push_notifications
                (request_id, author_guid, created_at, title, message, url, target, counter, status)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?);",
            [
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
                WHERE
                    status = ?
                LIMIT 12;",
                [AdminPushNotificationRequestStatus::DONE]
            );
    }

    public function updateRequestStartedOnDate(string $requestId): void
    {
        $query = (new PreparedStatement())
            ->query(
                "UPDATE system_push_notifications SET startedOn = ? AND status = ? WHERE request_id = ?;",
                [
                    new Timestamp(time(), 0),
                    AdminPushNotificationRequestStatus::IN_PROGRESS,
                    $requestId
                ]
            );

        $this->cassandraClient->request($query);
    }

    public function updateRequestCompletedOnDate(string $requestId, int $status): void
    {
        $query = (new PreparedStatement())
            ->query(
                "UPDATE system_push_notifications SET completedOn = ? AND status = ? WHERE request_id = ?;",
                [
                    new Timestamp(time(), 0),
                    $status,
                    $requestId
                ]
            );

        $this->cassandraClient->request($query);
    }

    /**
     * @throws ServerErrorException
     */
    public function getByRequestId(string $requestId): AdminPushNotificationRequest
    {
        $query = (new PreparedStatement())
            ->query(
                "SELECT
                        *
                    FROM
                        system_push_notifications
                    WHERE
                        request_id = ?
                    LIMIT 1;",
                [
                    $requestId
                ]
            );

        $row = $this->cassandraClient->request($query)->first();
        return AdminPushNotificationRequest::fromArray($row);
    }
}
