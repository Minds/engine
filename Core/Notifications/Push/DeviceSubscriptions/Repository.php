<?php
namespace Minds\Core\Notifications\Push\DeviceSubscriptions;

use Cassandra\Bigint;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Di\Di;

class Repository
{
    /** @var Client */
    protected $cql;

    public function __construct(Client $cql = null)
    {
        $this->cql = $cql ?? Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * @param DeviceSubscriptionListOpts $opts
     * @return iterable<DeviceSubscription>
     */
    public function getList(DeviceSubscriptionListOpts $opts): iterable
    {
        $statement = "SELECT * FROM push_notifications_device_subscriptions WHERE user_guid = ?";
        $values = [ new Bigint($opts->getUserGuid())];

        $prepared = new Custom();
        $prepared->query($statement, $values);

        $rows = $this->cql->request($prepared);

        foreach ($rows as $row) {
            $deviceSubscription = new DeviceSubscription();
            $deviceSubscription->setUserGuid((string) $row['user_guid'])
                ->setToken($row['device_token'])
                ->setService($row['service']);
            yield $deviceSubscription;
        }
    }

    /**
     * @param DeviceSubscription $deviceSubscription
     * @return bool
     */
    public function add(DeviceSubscription $deviceSubscription): bool
    {
        $statement = "INSERT INTO push_notifications_device_subscriptions
            (user_guid, device_token, service)
            VALUES (?, ?, ?)";
        
        $values = [
            new Bigint($deviceSubscription->getUserGuid()),
            $deviceSubscription->getToken(),
            $deviceSubscription->getService(),
        ];

        $prepared = new Custom();
        $prepared->query($statement, $values);

        return !!$this->cql->request($prepared);
    }

    /**
     * @param DeviceSubscription $deviceSubscription
     * @return bool
     */
    public function delete(DeviceSubscription $deviceSubscription): bool
    {
        $statement = "DELETE FROM push_notifications_device_subscriptions
            WHERE user_guid = ?
                AND device_token = ?";
        
        $values = [
            new Bigint($deviceSubscription->getUserGuid()),
            $deviceSubscription->getToken()
        ];

        $prepared = new Custom();
        $prepared->query($statement, $values);

        return !!$this->cql->request($prepared);
    }
}
