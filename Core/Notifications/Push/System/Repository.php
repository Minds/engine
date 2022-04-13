<?php

namespace Minds\Core\Notifications\Push\System;

use Cassandra\Bigint;
use Minds\Core\Data\Cassandra\Client as CassandraClient;
use Minds\Core\Data\Cassandra\Prepared\Custom as PreparedStatement;
use Minds\Core\Di\Di;
use Minds\Core\Notifications\Push\PushNotificationInterface;
use Minds\Entities\User;

class Repository
{
    private User $user;

    public function __construct(
        private ?CassandraClient $cassandraClient = null
    ) {
        $this->cassandraClient ??= Di::_()->get('Database\Cassandra\Client');
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function add(
        PushNotificationInterface $notification
    ): void {
        $query = $this->buildAddQuery($notification);
        $this->cassandraClient->request($query);
    }

    private function buildAddQuery(PushNotificationInterface $notification): PreparedStatement
    {
        $query = new PreparedStatement();
        return $query->query(
            "INSERT INTO " .
            "system_push_notifications " .
            "(author_guid, created_on, title, message, link, status)" .
            "VALUES " .
            "(?, ?, ?, ?, ?, ?);",
            [
                new Bigint($this->user->getGuid()),
                time(),
                $notification->getTitle(),
                $notification->getMessage(),
                $notification->getLink(),
                NotificationStatus::PENDING
            ]
        );
    }
}
