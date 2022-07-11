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
     * @return iterable<User>|void
     */
    public function getSubscriptionsThatSubscribeTo(
        string $userGuid,
        string $subscribedToGuid,
        int $limit = 12
    ): iterable {
        $statement = "SELECT own.friend_guid, "
            // Below we will do a subquery so that we can order the list by
            // how many other subscriptions we share. This can probably be improved
            // at a later date to be ordered by common interactions.
            . "( 
                SELECT count(*) FROM friends as own2
                    INNER JOIN friends others2 USING (friend_guid)
                WHERE own2.user_guid = :user_guid
                    AND others2.user_guid = others.user_guid
                ) as wider_mutual_count "
            . $this->getSubscriptionsThatSubscribeToStatement()
            . " ORDER BY wider_mutual_count DESC"
            . " LIMIT $limit";

        $prepared = $this->client->getConnection(Client::CONNECTION_REPLICA)->prepare($statement);

        $prepared->execute([
            'user_guid' => $userGuid,
            'friend_guid' => $subscribedToGuid,
        ]);

        foreach ($prepared as $row) {
            $user = $this->entitiesBuilder->single($row['friend_guid']);
            if (!$user instanceof User) {
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
                AND others.friend_guid = :friend_guid";
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
