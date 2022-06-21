<?php

namespace Minds\Core\Nostr;

use Minds\Common\Urn;
use Minds\Core\Data\Cassandra\Client as CassandraClient;
use Minds\Core\Data\Cassandra\Prepared\Custom as PreparedQuery;
use Minds\Core\Di\Di;
use Minds\Exceptions\NotFoundException;

/**
 *
 */
class Repository
{
    public function __construct(
        private ?CassandraClient $cassandraClient = null
    ) {
        $this->cassandraClient ??= Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * Fetches the entity guid from associated to a Nostr hash
     * @param string $hash
     * @return string
     * @throws NotFoundException
     */
    public function getEntityGuidByNostrHash(string $hash): string
    {
        $query = (new PreparedQuery())
            ->query(
                "SELECT * FROM nostr_hashes_to_entities WHERE hash = ?",
                [$hash]
            );

        $rows = $this->cassandraClient->request($query);

        if (!$rows->count()) {
            throw new NotFoundException("No entries were found for the provided hash");
        }

        $entityUrn = new Urn($rows->first()['entity_urn']);

        return $entityUrn->getNss();
    }

    /**
     * Adds a new entry in the table that links a nostr hash to a Minds Entity via URN
     * @param string $nostrHash
     * @param string $urn
     * @return bool
     */
    public function addNewCorrelation(string $nostrHash, string $urn): bool
    {
        $query = (new PreparedQuery())
            ->query(
                "INSERT INTO nostr_hashes_to_entities (hash, entity_urn) VALUES (?, ?)",
                [$nostrHash, $urn]
            );

        return $this->cassandraClient->request($query);
    }
}
