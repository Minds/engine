<?php

namespace Minds\Core\Nostr;

use Exception;
use Generator;
use Minds\Common\Urn;
use Minds\Core\Data\Cassandra\Client as CassandraClient;
use Minds\Core\Data\Cassandra\Prepared\Custom as PreparedQuery;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver as EntitiesResolver;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;

/**
 *
 */
class Repository
{
    public function __construct(
        private ?CassandraClient $cassandraClient = null,
        private ?EntitiesResolver $entitiesResolver = null
    ) {
        $this->cassandraClient ??= Di::_()->get('Database\Cassandra\Cql');
        $this->entitiesResolver ??= new EntitiesResolver();
    }

    /**
     * Fetches the users associated to a Nostr public key
     * @param array $authors
     * @return User[]
     * @throws NotFoundException
     * @throws Exception
     */
    public function getUserGuidsFromAuthors(array $authors): Generator
    {
        $queryString = "SELECT * FROM nostr_hashes_to_entities WHERE hash IN (";
        $queryValues = [];

        foreach ($authors as $author) {
            $queryString .= "?, ";
            $queryValues[] = $author;
        }

        $queryString = rtrim($queryString, ', ') . ")";
        
        $query = (new PreparedQuery())
            ->query(
                $queryString,
                $queryValues
            );

        $rows = $this->cassandraClient->request($query);

        if (!$rows->count()) {
            throw new NotFoundException("No entries were found for the provided hash");
        }

        foreach ($rows as $row) {
            yield $this->entitiesResolver->single(new Urn($row['entity_urn']));
        }
    }

    /**
     * Adds a new entry in the table that links a nostr hash to a Minds Entity via URN
     * @param string $nostrHash
     * @param string $urn
     * @param string $publicKey
     * @return bool
     */
    public function addNewCorrelation(string $nostrHash, string $urn, string $publicKey): bool
    {
        $query = (new PreparedQuery())
            ->query(
                "INSERT INTO nostr_hashes_to_entities (hash, public_key, entity_urn) VALUES (?, ?, ?)",
                [$nostrHash, $publicKey, $urn]
            );

        return $this->cassandraClient->request($query) !== false;
    }
}
