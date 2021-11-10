<?php
namespace Minds\Core\Feeds\TwitterSync;

use Cassandra\Bigint;
use Cassandra\Timestamp;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\UserErrorException;

class Repository
{
    public function __construct(protected Client $db)
    {
    }

    /**
     * Returns a list of connected account
     * @param string $userGuid
     * @return iterable<ConnectedAccount>
     */
    public function getList(string $userGuid = null): iterable
    {
        $statement = "SELECT * FROM twitter_sync";
        $values = [];

        if ($userGuid) {
            $statement .= "  WHERE user_guid = ?";
            $values = [ new Bigint($userGuid) ];
        }

        $prepared = new Custom();
        $prepared->query($statement, $values);

        $response = $this->db->request($prepared);

        if (!$response || !$response[0]) {
            throw new NotFoundException();
        }
        
        foreach ($response as $row) {
            $twitterUser = new TwitterUser();
            $twitterUser->setUserId((string) $row['twitter_user_id'])
                ->setUsername($row['twitter_username'])
                ->setFollowersCount($row['twitter_followers_count']);

            $connectedAccount = new ConnectedAccount();
            $connectedAccount->setUserGuid((string) $row['user_guid'])
                ->setTwitterUser($twitterUser)
                ->setLastImportedTweetId((string) $row['last_imported_tweet_id'])
                ->setLastSyncUnixTs(isset($row['last_sync_ts']) ? $row['last_sync_ts']->time() : time())
                ->setDiscoverable($row['discoverable'])
                ->setConnectedTimestampSeconds($row['connected_timestamp']->time());

            yield $connectedAccount;
        }
    }

    /**
     * Returns a single connected account from a user_guid
     * @param string $userGuid
     * @return ConnectAccount
     */
    public function get(string $userGuid): ConnectedAccount
    {
        return iterator_to_array($this->getList(userGuid: $userGuid))[0];
    }

    /**
     * Adds connected account to the database
     * @param ConnectedAccount $connectedAccount
     * @return bool
     */
    public function add(ConnectedAccount $connectedAccount): bool
    {
        $statement = "INSERT INTO twitter_sync (
            user_guid,
            twitter_user_id,
            twitter_username,
            twitter_followers_count,
            last_imported_tweet_id,
            last_sync_ts,
            discoverable,
            connected_timestamp
            ) VALUES (?,?,?,?,?,?,?,?)";

        $values = [
            new Bigint($connectedAccount->getUserGuid()),
            new Bigint($connectedAccount->getTwitterUser()->getUserId()),
            (string) $connectedAccount->getTwitterUser()->getUsername(),
            (int) $connectedAccount->getTwitterUser()->getFollowersCount(),
            new Bigint($connectedAccount->getLastImportedTweetId()),
            new Timestamp($connectedAccount->getLastSyncUnixTs(), 0),
            $connectedAccount->isDiscoverable(),
            new Timestamp($connectedAccount->getConnectedTimestampSeconds(), 0),
        ];

        $prepared = new Custom();
        $prepared->query($statement, $values);

        return (bool) $this->db->request($prepared);
    }

    /**
     * Removes connected account
     * @param ConnectedAccount $connectedAccount
     * @return bool
     */
    public function delete(ConnectedAccount $connectedAccount): bool
    {
        $statement = "DELETE FROM twitter_sync
                        WHERE user_guid = ?";
        $values = [ new Bigint($connectedAccount->getUserGuid()), ];

        $prepared = new Custom();
        $prepared->query($statement, $values);

        return (bool) $this->db->request($prepared);
    }
}
