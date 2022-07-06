<?php

namespace Minds\Core\Notifications\Push\System\Targets;

use Exception;
use Generator;
use Minds\Core\Data\Cassandra\Prepared\Custom as PreparedStatement;
use Minds\Core\Data\Cassandra\Scroll as CassandraClient;
use Minds\Core\Di\Di;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;

/**
 * Responsible to retrieve all Android app device subscriptions currently registered on Minds.
 */
class AllAndroidAppDevices implements SystemPushNotificationTargetInterface
{
    /**
     * Constructor.
     * @param ?CassandraClient $cassandraClient - cassandra client.
     */
    public function __construct(
        private ?CassandraClient $cassandraClient = null
    ) {
        $this->cassandraClient ??= Di::_()->get('Database\Cassandra\Cql\Scroll');
    }

    /**
     * Gets list of device subscriptions.
     * @return Generator<DeviceSubscription>
     * @throws Exception
     */
    public function getList(): Generator
    {
        $query = $this->buildQuery();

        foreach ($this->cassandraClient->request($query) as $deviceSubscription) {
            yield (new DeviceSubscription())
                ->setUserGuid((string) $deviceSubscription['user_guid'])
                ->setToken($deviceSubscription['device_token'])
                ->setService($deviceSubscription['service']);
        }
    }

    /**
     * Builds query to get all Android app devices based on whether the service given for the
     * device subscription is 'fcm'.
     * @return PreparedStatement - prepared query statement.
     */
    private function buildQuery(): PreparedStatement
    {
        $statement = new PreparedStatement();
        return $statement->query("SELECT * FROM push_notifications_device_subscriptions WHERE service='fcm'");
    }
}
