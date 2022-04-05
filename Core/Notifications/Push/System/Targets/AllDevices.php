<?php

namespace Minds\Core\Notifications\Push\System\Targets;

use Generator;
use Minds\Core\Data\Cassandra\Client as CassandraClient;
use Minds\Core\Data\Cassandra\Prepared\Custom as PreparedStatement;
use Minds\Core\Di\Di;

class AllDevices implements SystemPushNotificationTargetInterface
{
    public function __construct(
        private ?CassandraClient $cassandraClient = null
    ) {
        $this->cassandraClient ??= Di::_()->get('Database\Cassandra\Cql');
    }

    public function getList(): Generator
    {
    }

    private function buildQuery(): PreparedStatement
    {
        $statement = new PreparedStatement();
        return $statement->query("SELECT * FROM push_notifications_device_subscriptions");
    }
}
