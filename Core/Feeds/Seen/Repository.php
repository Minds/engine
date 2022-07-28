<?php
namespace Minds\Core\Feeds\Seen;

use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di\Di;

/**
 * Repository for storing 'seen-entities' by pseudo id
 */
class Repository
{
    public function __construct(private ?Client $client = null)
    {
        $this->client ??= Di::_()->get('Database\MySQL\Client');
    }

    /**
     * Adds a seen entity to database
     * @param SeenEntity $seenEntity
     * @return bool
     */
    public function add(SeenEntity $seenEntity): bool
    {
        $statement = "INSERT INTO pseudo_seen_entities
            (pseudo_id, entity_guid, last_seen_timestamp) VALUES (:pseudo_id, :entity_guid, :last_seen_timestamp)
            ON DUPLICATE KEY UPDATE last_seen_timestamp=:last_seen_timestamp";
        
        $prepared = $this->client->getConnection(Client::CONNECTION_MASTER)->prepare($statement);
        
        return $prepared->execute([
            'pseudo_id' => $seenEntity->getPseudoId(),
            'entity_guid' => $seenEntity->getEntityGuid(),
            'last_seen_timestamp' => date('c', $seenEntity->getLastSeenTimestamp()),
        ]);
    }

    /**
     * Returns a list of seen entities. Will return most recent first.
     * @param string $pseudoId
     * @param int $limit
     * @param int $offset
     */
    public function getList(string $pseudoId, int $limit = 100, int $offset = 0): iterable
    {
        $statement = "SELECT * FROM pseudo_seen_entities
            WHERE pseudo_id = :pseudo_id
            ORDER BY last_seen_timestamp DESC
            LIMIT $offset,$limit";

        $prepared = $this->client->getConnection(Client::CONNECTION_REPLICA)->prepare($statement);

        $prepared->execute([
            'pseudo_id' => $pseudoId,
        ]);

        foreach ($prepared as $row) {
            yield new SeenEntity(
                $row['pseudo_id'],
                $row['entity_guid'],
                strtotime($row['last_seen_timestamp'])
            );
        }

        return;
    }
}
