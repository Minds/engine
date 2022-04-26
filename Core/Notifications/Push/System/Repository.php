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
    private User $user;

    public function __construct(
        private ?CassandraClient $cassandraClient = null
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
        return $query->query(
            "INSERT INTO
                system_push_notifications
                (request_id, author_guid, created_on, title, message, url, status)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?);",
            [
                new Timeuuid($notification->getRequestId()),
                new Bigint($this->user->getGuid()),
                new Timestamp(time(), 0),
                $notification->getTitle(),
                $notification->getMessage(),
                $notification->getLink(),
                AdminPushNotificationRequestStatus::PENDING
            ]
        );
    }

    /**
     * @return AdminPushNotificationRequest[]
     */
    public function getCompletedRequests(): array
    {
        $query = $this->buildFetchCompletedRequests();
        $notifications = [];

        foreach ($this->cassandraClient->request($query) as $notification) {
            $notifications[] = (new AdminPushNotificationRequest())
                ->setTitle($notification['title'])
                ->setMessage($notification['message'])
                ->setLink($notification['message'])
                ->setRequestId($notification['request_id'])
                ->setAuthorId($notification['author_guid'])
                ->setCreatedAt(strtotime($notification['createdOn']))
                ->setStatus($notification['status'])
                ->setCounter($notification['counter'])
                ->setTarget($notification['target']);
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
                LIMIT 12
                ALLOW FILTERING;",
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
                    new Timeuuid($requestId)
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
                    new Timeuuid($requestId)
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
                    new Timeuuid($requestId)
                ]
            );

        $row = $this->cassandraClient->request($query)->first();
        return AdminPushNotificationRequest::fromArray($row);
    }
}
