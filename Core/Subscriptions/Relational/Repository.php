<?php
namespace Minds\Core\Subscriptions\Relational;

use Minds\Common\Repository\Response;
use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Subscriptions\Subscription;
use Minds\Entities\User;
use PDO;

class Repository
{
    public function __construct(
        protected ?Client $client = null,
        protected ?EntitiesBuilder $entitiesBuilder = null,
        protected ?Config $config = null,
    ) {
        $this->client ??= Di::_()->get('Database\MySQL\Client');
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->config ??= Di::_()->get(Config::class);
    }


    /**
     * Add a subscription to the sql datastore
     * @param Subscription $subscription
     * @return bool
     */
    public function add(Subscription $subscription): bool
    {
        $statement = "INSERT INTO friends (user_guid, friend_guid, tenant_id, timestamp) VALUES (:user_guid, :friend_guid, :tenant_id, CURRENT_TIMESTAMP()) ON DUPLICATE KEY UPDATE user_guid=user_guid";
        
        $prepared = $this->client->getConnection(Client::CONNECTION_MASTER)->prepare($statement);
        
        return $prepared->execute([
            'user_guid' => $subscription->getSubscriberGuid(),
            'friend_guid' => $subscription->getPublisherGuid(),
            'tenant_id' => $this->getTenantId(),
        ]);
    }

    /**
     * Delete a subscripiption
     * @param Subscription $subscription
     * @return Subscription|bool
     */
    public function delete(Subscription $subscription): bool
    {
        $statement = "DELETE FROM friends
                        WHERE user_guid = :user_guid
                        AND friend_guid = :friend_guid";

        $values = [
            'user_guid' => $subscription->getSubscriberGuid(),
            'friend_guid' => $subscription->getPublisherGuid(),
        ];

        if ($tenantId = $this->getTenantId()) {
            $statement .= " AND tenant_id = :tenant_id";
            $values['tenant_id'] = $tenantId;
        } else {
            $statement .= " AND tenant_id IS NULL";
        }

        $prepared = $this->client->getConnection(Client::CONNECTION_MASTER)->prepare($statement);

        return $prepared->execute($values);
    }

    /**
     * Checks if a user is subscribed to another user
     */
    public function isSubscribed(int $userGuid, int $friendGuid): bool
    {
        $statement = "SELECT * FROM friends
            WHERE user_guid = :user_guid
            AND friend_guid = :friend_guid";

        $values = [
            'user_guid' => $userGuid,
            'friend_guid' => $friendGuid,
        ];

        if ($tenantId = $this->getTenantId()) {
            $statement .= " AND tenant_id = :tenant_id";
            $values['tenant_id'] = $tenantId;
        } else {
            $statement .= " AND tenant_id IS NULL";
        }

        $prepared = $this->client->getConnection(Client::CONNECTION_MASTER)->prepare($statement);

        $prepared->execute($values);

        return ($prepared->rowCount() > 0);
    }

    /**
     * Returns a count of users a user is subscribed to
     */
    public function getSubscriptionsCount(int $userGuid): int
    {
        $statement = "SELECT count(*) as c 
            FROM friends
            WHERE user_guid = :user_guid";

        $values = [
            'user_guid' => $userGuid,
        ];

        if ($this->getTenantId()) {
            $statement .= " AND tenant_id = :tenant_id";
            $values['tenant_id'] = $this->getTenantId();
        } else {
            $statement .= " AND tenant_id IS NULL";
        }

        $prepared = $this->client->getConnection(Client::CONNECTION_MASTER)->prepare($statement);

        $prepared->execute($values);

        $result = $prepared->fetchAll(PDO::FETCH_BOTH);

        return $result[0]['c'];
    }

    /**
     * Returns a count of users that are subscribed to a user
     */
    public function getSubscribersCount(int $userGuid): int
    {
        $statement = "SELECT count(*) as c 
            FROM friends
            WHERE friend_guid = :user_guid";

        $values = [
            'user_guid' => $userGuid,
        ];

        if ($this->getTenantId()) {
            $statement .= " AND tenant_id = :tenant_id";
            $values['tenant_id'] = $this->getTenantId();
        } else {
            $statement .= " AND tenant_id IS NULL";
        }

        $prepared = $this->client->getConnection(Client::CONNECTION_MASTER)->prepare($statement);

        $prepared->execute($values);

        $result = $prepared->fetchAll(PDO::FETCH_BOTH);

        return $result[0]['c'];
    }

    /**
     * Get a list of subscribers.
     * @param int $userGuid - the user to get subscribers for.
     * @param int $limit - how many to return.
     * @param int|null $loadBefore - timestamp to load before.
     * @return Response - a response object with the users as the data.
     */
    public function getSubscribers(
        int $userGuid,
        int $limit = 12,
        int $loadBefore = null
    ): Response {
        $statement = "SELECT * 
            FROM friends
            WHERE friend_guid = :user_guid";

        $values = ['user_guid' => $userGuid];

        if ($loadBefore) {
            $statement .= " AND timestamp < :load_before";
            $values['load_before'] = date('c', $loadBefore);
        }

        if ($this->getTenantId()) {
            $statement .= " AND tenant_id = :tenant_id";
            $values['tenant_id'] = $this->getTenantId();
        } else {
            $statement .= " AND tenant_id IS NULL";
        }

        $statement .= " ORDER BY timestamp DESC";
        $statement .= " LIMIT :limit";
        $values['limit'] = $limit;

        $prepared = $this->client->getConnection(Client::CONNECTION_MASTER)->prepare($statement);

        $this->client->bindValuesToPreparedStatement($prepared, $values);

        $prepared->execute();

        $result = $prepared->fetchAll(PDO::FETCH_ASSOC);
        $response = [];
        $pagingToken = null;

        foreach ($result as $key => $row) {
            $response[$key] = $this->entitiesBuilder->single($row['user_guid']);
            $pagingToken = strtotime($row['timestamp']);
        }

        return (new Response($response))
            ->setPagingToken($pagingToken);
    }

    /**
     * Will return subscriptions of subscriptions, ordered by most relevant
     * NOTE: Users with >1000 subscriptions will only take into account their most recent 1000 subscriptions
     * @param string $userGuid
     * @param int $limit
     * @param int $offset
     * @return iterable<User>
     */
    public function getSubscriptionsOfSubscriptions(
        string $userGuid,
        int $limit = 3,
        int $offset = 0
    ): iterable {
        $statement = "
            SELECT 
                a.friend_guid, 
                COUNT(*) as relevance
            FROM 
                friends a
            JOIN 
                (
                    SELECT user_guid, friend_guid FROM friends
                    WHERE user_guid=:user_guid
                    ORDER BY timestamp DESC
                    LIMIT 1000
                ) as b
                ON  (
                        b.friend_guid = a.user_guid
                    )
            LEFT JOIN
                friends c
                ON
                    (
                        c.friend_guid = a.friend_guid 
                        AND c.user_guid = b.user_guid
                    )     
            WHERE 
                c.user_guid IS NULL
            AND 
                a.friend_guid != :user_guid
            AND
                a.timestamp >  DATE_SUB( CURDATE(), INTERVAL 1 YEAR ) 
            GROUP BY 
                a.friend_guid
            ORDER BY 
                relevance DESC
            LIMIT $offset,$limit";

        $prepared = $this->client->getConnection(Client::CONNECTION_REPLICA)->prepare($statement);
    
        $prepared->execute([
                'user_guid' => $userGuid,
            ]);
    
        foreach ($prepared as $row) {
            $user = $this->entitiesBuilder->single($row['friend_guid']);
            if (!$user instanceof User || !$user->isEnabled()) {
                // We may want to log this as you shouldn't be subscribed to a blocked or non-existant user
                continue;
            }
            yield $user;
        }

        return;
    }

    /**
     * Returns count of users who **I subscribe to** that also subscribe to this users
     * @param $userGuid - eg. yourself
     * @param $subscribedToGuid - eg. your friend
     * @param $limit - how many results you want
     */
    public function getSubscriptionsThatSubscribeToCount(
        string $userGuid,
        string $subscribedToGuid
    ): int {
        $statement = "SELECT count(*) as c " . $this->getSubscriptionsThatSubscribeToStatement();
    
        $prepared = $this->client->getConnection(Client::CONNECTION_REPLICA)->prepare($statement);

        $prepared->execute([
            'user_guid' => $userGuid,
            'friend_guid' => $subscribedToGuid,
        ]);

        return (int) $prepared->fetchAll()[0]['c'];
    }

    /**
     * Returns users who **I subscribe to** that also subscribe to this users
     * @param $userGuid - eg. yourself
     * @param $subscribedToGuid - eg. your friend
     * @param $limit - how many results you want
     * @param $randomize - if true (default) will return results in a random order
     * @return iterable<User>|void
     */
    public function getSubscriptionsThatSubscribeTo(
        string $userGuid,
        string $subscribedToGuid,
        int $limit = 12,
        int $offset = 0,
        bool $randomize = true,
    ): iterable {
        $statement = "SELECT own.friend_guid " . $this->getSubscriptionsThatSubscribeToStatement();

        if ($randomize) {
            $statement . " ORDER BY RAND()";
        }

        $limit = (int) $limit;
        $offset = (int) $offset;

        $statement .= " LIMIT $offset,$limit";

        $prepared = $this->client->getConnection(Client::CONNECTION_REPLICA)->prepare($statement);

        $prepared->execute([
            'user_guid' => $userGuid,
            'friend_guid' => $subscribedToGuid,
        ]);

        foreach ($prepared as $row) {
            $user = $this->entitiesBuilder->single($row['friend_guid']);
            if (!$user instanceof User || !$user->isEnabled()) {
                // We may want to log this as you shouldn't be subscribed to a blocked or non-existant user
                continue;
            }
            yield $user;
        }

        return;
    }

    /**
     * Reusable snippet that returns users **I** am subscribed to,
     * that are also subscribe to another user
     * @return string
     */
    private function getSubscriptionsThatSubscribeToStatement(): string
    {
        return "FROM friends own
            INNER JOIN friends others 
                ON own.friend_guid = others.user_guid
            WHERE own.user_guid = :user_guid 
                AND others.friend_guid = :friend_guid
                AND own.friend_guid != :user_guid";
    }

    private function getTenantId(): ?int
    {
        return $this->config->get('tenant_id');
    }

    /**
     * COPY minds.friends TO 'friends.csv' WITH DELIMITER='|';
     */

    /**
     * SHOW VARIABLES LIKE "secure_file_priv";
     */

    /**
     LOAD DATA INFILE '/vt/vtdataroot/vt_0720777458/tmp/friends.csv'
     IGNORE INTO TABLE friends
     FIELDS TERMINATED BY '|'
     LINES TERMINATED BY '\n'
     (user_guid, friend_guid, @unixts)
     SET timestamp = FROM_UNIXTIME(@unixts);
     */
}
