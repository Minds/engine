<?php

namespace Minds\Core\Nostr;

use Minds\Core\Data\MySQL;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PDO;
use PDOStatement;

/**
 *
 */
class Repository
{
    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?MySQL\Client $mysqlClient = null,
    ) {
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->mysqlClient ??= Di::_()->get('Database\MySQL\Client');
    }

    /**
     * Adds a public key to the whitelist
     * @param string $pubKey
     * @return bool
     */
    public function addToWhitelist(string $pubKey): bool
    {
        $statement = "INSERT nostr_pubkey_whitelist (pubkey) VALUES (?) ON DUPLICATE KEY UPDATE pubkey=pubkey";

        $values = [
           $pubKey
        ];

        $prepared = $this->mysqlClient->getConnection(MySQL\Client::CONNECTION_MASTER)->prepare($statement);
        return $prepared->execute($values);
    }

    /**
     * Returns true if user is on whitelist
     * @param string $pubKey
     * @return bool
     */
    public function isOnWhitelist(string $pubKey): bool
    {
        $statement = "SELECT * FROM nostr_pubkey_whitelist WHERE pubkey = ?";

        $values = [
            $pubKey,
        ];

        $prepared = $this->mysqlClient->getConnection(MySQL\Client::CONNECTION_REPLICA)->prepare($statement);
        $prepared->execute($values);

        $rows = $prepared->fetchAll();

        if (!$rows) {
            return false;
        }

        return true;
    }

    /**
     * Adds NostrEvents to a relatoional database to allow our relay to support external posts
     * @param NostrEvent $nostrEvent
     * @return bool
     */
    public function addEvent(NostrEvent $nostrEvent): bool
    {
        $statement = "INSERT nostr_events 
        (
            id,
            pubkey,
            created_at,
            kind,
            e_ref,
            p_ref,
            tags,
            content,
            sig
        )
        VALUES (?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE id=id";

        // Extract event and public key references if present
        $eRef = null;
        $pRef = null;
        foreach ($nostrEvent->getTags() as $tag) {
            if ($tag[0] == "e") {
                $eRef = $tag[1];
            }
            if ($tag[0] == "p") {
                $pRef = $tag[1];
            }
        }

        $values = [
            $nostrEvent->getId(),
            $nostrEvent->getPubKey(),
            date('c', $nostrEvent->getCreated_at()),
            $nostrEvent->getKind(),
            $eRef,
            $pRef,
            $nostrEvent->getTags() ? json_encode($nostrEvent->getTags()) : null,
            $nostrEvent->getContent(),
            $nostrEvent->getSig(),
        ];

        // First, persist event.
        $prepared = $this->mysqlClient->getConnection(MySQL\Client::CONNECTION_MASTER)->prepare($statement);
        return $prepared->execute($values);
    }

    /**
     * Return nostr events
     * @param array
     * @return iterable<NostrEvent>
     */
    public function getEvents(array $filters = []): iterable
    {
        $prepared = $this->executeEventsPreparedQuery($filters);

        foreach ($prepared->fetchAll() as $row) {
            $event = new NostrEvent();
            $event->setId($row['id'])
                ->setPubKey($row['pubkey'])
                ->setKind($row['kind'])
                ->setCreated_at(strtotime($row['created_at']))
                ->setContent($row['content'])
                ->setSig($row['sig']);

            // Tags can be null
            if (array_key_exists('tags', $row) && $row['tags'] != null) {
                $event->setTags(json_decode($row['tags']));
            }

            yield $event;
        }
    }

    /**
     * Return Activity entity from a NostrId
     * @param string $nostrId
     * @return Activity
     */
    public function getActivityFromNostrId(string $nostrId): ?Activity
    {
        $prepared = $this->executeEventsPreparedQuery([
            'ids' => [ $nostrId ],
            'kinds' => [1],
        ], returnActivityGuids: true);
    
        $rows = $prepared->fetchAll();

        if (isset($rows[0])) {
            $activityGuid = $rows[0]['activity_guid'];
            if (!$activityGuid) {
                return null;
            }

            $activity = $this->entitiesBuilder->single($activityGuid);
            if ($activity instanceof Activity) {
                return $activity;
            }
        }
    
        return null;
    }

    /**
     * Executre queries against the nostr_events table
     * @param array $filters
     * @param bool $returnActivityGuids - set to true if you want to do a join against nostr_kind_1_to_activity_guid table
     * @return PDOStatement
     */
    protected function executeEventsPreparedQuery(array $filters = [], bool $returnActivityGuids = false): PDOStatement
    {
        $filters = array_merge([
            'ids' => [],
            'authors' => [],
            'kinds' => [ 0, 1 ],
            '#e' => null,
            '#p' => null,
            'since' => null,
            'until' => null,
            'limit' => 12,
        ], $filters);


        $statement = "SELECT e.* FROM nostr_events e";
        $values = [];

        if ($returnActivityGuids) {
            $statement = "SELECT e.*, a.activity_guid FROM nostr_events e
                            LEFT OUTER JOIN nostr_kind_1_to_activity_guid a
                            ON a.id = e.id";
        }

        $where = [];

        if ($filters['ids']) {
            $where[] = "e.id IN " . $this->inPad($filters['ids']);
            array_push($values, ...$filters['ids']);
        }

        if ($filters['authors']) {
            $where[] = "e.pubkey IN " . $this->inPad($filters['authors']);
            array_push($values, ...$filters['authors']);
        }

        if ($filters['kinds']) {
            $where[] = "e.kind IN " . $this->inPad($filters['kinds']);
            array_push($values, ...$filters['kinds']);
        }

        if ($filters['#e']) {
            $where[] = "e.e_ref IN " . $this->inPad($filters['#e']);
            array_push($values, ...$filters['#e']);
        }

        if ($filters['#p']) {
            $where[] = "e.p_ref IN " . $this->inPad($filters['#p']);
            array_push($values, ...$filters['#p']);
        }

        if ($filters) {
            $statement .= " WHERE " . implode(' AND ', $where);
        }

        $prepared = $this->mysqlClient->getConnection(MySQL\Client::CONNECTION_REPLICA)->prepare($statement);

        $prepared->execute($values);

        return $prepared;
    }

    /**
     * Effectively indexes a Minds Activity posts to a Nostr ID
     * @param Activity $activity
     * @param string $nostrId
     * @return bool
     */
    public function addActivityToNostrId(Activity $activity, string $nostrId): bool
    {
        $statement = "INSERT INTO nostr_kind_1_to_activity_guid
        (
            id,
            activity_guid,
            owner_guid,
            is_external
        )
        VALUES (?,?,?,?)";

        $prepared = $this->mysqlClient->getConnection(MySQL\Client::CONNECTION_MASTER)->prepare($statement);

        $prepared->bindParam(1, $nostrId, PDO::PARAM_STR);

        $activityGuid = $activity->getGuid();
        $prepared->bindParam(2, $activityGuid, PDO::PARAM_STR);

        $ownerGuid = $activity->getOwnerGuid();
        $prepared->bindParam(3, $ownerGuid, PDO::PARAM_STR);
        
        $isExternal = $activity->getSource() === 'nostr';
        $prepared->bindParam(4, $isExternal, PDO::PARAM_BOOL);

        return $prepared->execute();
    }


    /**
     * Adds a Minds User and Nostr public key pairing
     * @param User $user
     * @param string $nostrPublicKey
     * @return bool
     */
    public function addNostrUser(User $user, string $nostrPublicKey): bool
    {
        $statement = "INSERT INTO nostr_users
        (
            pubkey,
            user_guid,
            is_external
        )
        VALUES (?,?,?)
        ON DUPLICATE KEY UPDATE pubkey=pubkey
        ";

        $prepared = $this->mysqlClient->getConnection(MySQL\Client::CONNECTION_MASTER)->prepare($statement);

        $prepared->bindParam(1, $nostrPublicKey, PDO::PARAM_STR);

        $userGuid = $user->getGuid();
        $prepared->bindParam(2, $userGuid, PDO::PARAM_STR); // Bigint

        $isExternal = $user->getSource() === 'nostr';
        $prepared->bindParam(3, $isExternal, PDO::PARAM_BOOL);

        return $prepared->execute();
    }

    /**
     * Returns a Minds User from a Nostr public key
     * @param string $nostrPublicKey
     * @return User|null
     */
    public function getUserFromNostrPublicKey(string $nostrPublicKey): ?User
    {
        $statement = "SELECT * FROM nostr_users WHERE pubkey = ?";

        $values = [
            $nostrPublicKey,
        ];

        $prepared = $this->mysqlClient->getConnection(MySQL\Client::CONNECTION_REPLICA)->prepare($statement);
        $prepared->execute($values);

        $rows = $prepared->fetchAll();

        if (!$rows) {
            return null;
        }

        $userGuid = $rows[0]['user_guid'];
        $user = $this->entitiesBuilder->single($userGuid);

        if ($user instanceof User) {
            return $user;
        }

        return null;
    }

    /**
     * Returns Minds Users from an array of Nostr public keys
     * @param string[] $nostrPublicKey
     * @return User[]
     */
    public function getUserFromNostrPublicKeys(array $nostrPublicKeys): array
    {
        $statement = "SELECT * FROM nostr_users WHERE pubkey IN " . $this->inPad($nostrPublicKeys);

        $values = [
            ...$nostrPublicKeys,
        ];

        $prepared = $this->mysqlClient->getConnection(MySQL\Client::CONNECTION_REPLICA)->prepare($statement);
        $prepared->execute($values);

        /** @var User[] */
        $users = [];

        foreach ($prepared->fetchAll() as $row) {
            $userGuid = $row['user_guid'];
            $user = $this->entitiesBuilder->single($userGuid);

            if ($user instanceof User) {
                $users[] = $user;
            }
        }

        return $users;
    }

    /**
     * Helper function to pad out IN (?,?,?)
     * @param array $arr
     * @return string
     */
    protected function inPad(array $arr): string
    {
        return '(' . rtrim(str_repeat('?,', count($arr)), ',') . ')';
    }
}
