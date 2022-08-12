<?php
namespace Minds\Core\Subscriptions\Relational;

use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Subscriptions\Subscription;
use Minds\Entities\User;

class Repository
{
    public function __construct(
        protected ?Client $client = null,
        protected ?EntitiesBuilder $entitiesBuilder = null
    ) {
        $this->client ??= Di::_()->get('Database\MySQL\Client');
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
    }


    /**
     * Add a subscription to the sql datastore
     * @param Subscription $subscription
     * @return bool
     */
    public function add(Subscription $subscription): bool
    {
        $statement = "INSERT INTO friends (user_guid, friend_guid, timestamp) VALUES (:user_guid, :friend_guid, CURRENT_TIMESTAMP())";
        
        $prepared = $this->client->getConnection(Client::CONNECTION_MASTER)->prepare($statement);
        
        return $prepared->execute([
            'user_guid' => $subscription->getSubscriberGuid(),
            'friend_guid' => $subscription->getPublisherGuid(),
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
                        WHERE user_guid = :user_guid and friend_guid = :friend_guid";
        $prepared = $this->client->getConnection(Client::CONNECTION_MASTER)->prepare($statement);

        return $prepared->execute([
            'user_guid' => $subscription->getSubscriberGuid(),
            'friend_guid' => $subscription->getPublisherGuid(),
        ]);
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
