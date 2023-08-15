<?php
namespace Minds\Core\ActivityPub;

use Minds\Core\ActivityPub\Types\Actor\AbstractActorType;
use Minds\Core\Data\MySQL\AbstractRepository;
use PDO;
use PDOException;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class Repository extends AbstractRepository
{
    public function getUrnFromUri(string $uri): ?string
    {
        $query = $this->mysqlClientReaderHandler
            ->select()
            ->columns([
                'uri',
                'domain',
                'entity_urn',
            ])
            ->from('minds_activitypub_uris')
            ->where('uri', Operator::EQ, new RawExp(':uri'));

        $stmt = $query->prepare();
        $stmt->execute([
            'uri' => $uri
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return null;
        }

        $row = $rows[0];

        return $row['entity_urn'];
    }

    public function getUriFromGuid(int $guid): ?string
    {
        $query = $this->mysqlClientReaderHandler
            ->select()
            ->columns([
                'uri',
            ])
            ->from('minds_activitypub_uris')
            ->where('entity_guid', Operator::EQ, new RawExp(':entityGuid'));

        $stmt = $query->prepare();
        $stmt->execute([
            'entityGuid' => $guid
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return null;
        }

        $row = $rows[0];

        return $row['uri'];
    }

    public function addUri(
        string $uri,
        string $domain,
        string $entityUrn,
        int $entityGuid
    ) {
        $query = $this->mysqlClientWriterHandler
            ->insert()
            ->into('minds_activitypub_uris')
            ->set([
                'uri' => new RawExp(':uri'),
                'domain' => new RawExp(':domain'),
                'entity_urn' => new RawExp(':entityUrn'),
                'entity_guid' => new RawExp(':entityGuid'),
            ]);
           

        $stmt = $query->prepare();
    
        return $stmt->execute([
            'uri' => $uri,
            'domain' => $domain,
            'entityUrn' => $entityUrn,
            'entityGuid' => $entityGuid,
        ]);
    }

    /**
     * Saves a remote actor to the datastore, so we have efficient access to their properties without HTTP requests
     */
    public function addActor(AbstractActorType $actor): bool
    {
        $query = $this->mysqlClientWriterHandler
            ->insert()
            ->into('minds_activitypub_actors')
            ->set([
                'uri' => new RawExp(':uri'),
                'type' => new RawExp(':type'),
                'inbox' => new RawExp(':inbox'),
                'outbox' => new RawExp(':outbox'),
                'shared_inbox' => new RawExp(':sharedInbox'),
                'url' => new RawExp(':url'),
            ]);
           

        $stmt = $query->prepare();
    
        return $stmt->execute([
            'uri' => $actor->id,
            'type' => $actor->getType(),
            'inbox' => $actor->inbox,
            'outbox' => $actor->outbox,
            'sharedInbox' => $actor->endpoints['sharedInbox'] ?? null,
            'url' => $actor->url
        ]);
    }

    /**
     * Returns a private key for a local user
     */
    public function getPrivateKey(int $userGuid): ?string
    {
        $query = $this->mysqlClientReaderHandler
            ->select()
            ->columns([
                'private_key',
            ])
            ->from('minds_activitypub_keys')
            ->where('user_guid', Operator::EQ, new RawExp(':userGuid'));

        $stmt = $query->prepare();
        $stmt->execute([
            'userGuid' => $userGuid
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return null;
        }

        $row = $rows[0];

        return $row['private_key'];
    }

    /**
     * Saves private key for local user
     */
    public function addPrivateKey(
        int $userGuid,
        string $privateKey
    ): bool {
        $query = $this->mysqlClientWriterHandler
            ->insert()
            ->into('minds_activitypub_keys')
            ->set([
                'user_guid' => new RawExp(':userGuid'),
                'private_key' => new RawExp(':privateKey'),
            ]);

        $stmt = $query->prepare();
    
        return $stmt->execute([
            'userGuid' => $userGuid,
            'privateKey' => $privateKey,
        ]);
    }

    public function getInboxesForFollowers(int $userGuid): iterable
    {
        $query = $this->mysqlClientReaderHandler
            ->select()
            ->columns([
                
                'inbox' => new RawExp('coalesce(shared_inbox, inbox)'),
            ])
            ->from('minds_activitypub_actors')
            ->innerJoin('minds_activitypub_uris', 'minds_activitypub_uris.uri', Operator::EQ, 'minds_activitypub_actors.uri')
            ->innerJoin('friends', 'friends.user_guid', Operator::EQ, 'minds_activitypub_uris.entity_guid')
            ->where('friends.friend_guid', Operator::EQ, new RawExp(':userGuid'));

        $stmt = $query->prepare();
        $stmt->execute([
            'userGuid' => $userGuid
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            yield $row['inbox'];
        }
    }
}
