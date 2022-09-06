<?php

namespace Minds\Core\Rewards\Restrictions\Blockchain;

use Cassandra\Timestamp;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Di\Di;

/**
 * Repository for storing and retrieving Restrictions from the database.
 */
class Repository
{
    public function __construct(
        private ?Client $client = null
    ) {
        $this->client ??= Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * Get all Restrictions.
     * @return array array of all restrictions.
     */
    public function getAll(): array
    {
        $statement =  "SELECT * FROM blockchain_restricted_addresses";

        $prepared = new Custom();
        $prepared->query($statement);

        $rows = $this->client->request($prepared);

        foreach ($rows as $row) {
            $response[] = (new Restriction())
                ->setAddress($row['address'])
                ->setReason($row['reason'])
                ->setNetwork($row['network'])
                ->setTimeAdded($row['time_added']);
        }

        return $response ?? [];
    }

    /**
     * Get a restriction by address.
     * @param string $address - address to get.
     * @return array array of matching restrictions.
     */
    public function get(string $address): array
    {
        $reasonsClause = $this->getReasonsClause();
        $statement =  "SELECT * FROM blockchain_restricted_addresses WHERE $reasonsClause AND address = ?";
        $values = [ $address ];

        $prepared = new Custom();
        $prepared->query($statement, $values);

        $rows = $this->client->request($prepared);

        foreach ($rows as $row) {
            $response[] = (new Restriction())
                ->setAddress($row['address'])
                ->setReason($row['reason'])
                ->setNetwork($row['network'])
                ->setTimeAdded($row['time_added']);
        }

        return $response ?? [];
    }

    /**
     * Add a Restriction to the database.
     * @param Restriction $restriction - object to add.
     * @return bool true if added.
     */
    public function add(Restriction $restriction): bool
    {
        $statement = "INSERT INTO blockchain_restricted_addresses
            (address, reason, network, time_added)
            VALUES (?, ?, ?, ?)";
        $values = [
            $restriction->getAddress(),
            $restriction->getReason(),
            $restriction->getNetwork(),
            new Timestamp(time(), 0)
        ];

        $query = new Custom();
        $query->query($statement, $values);
        return (bool) $this->client->request($query);
    }

    /**
     * Delete a Restriction to the database by address.
     * @param string $address - address to delete entries for.
     * @return bool true if deleted.
     */
    public function delete(string $address): bool
    {
        $reasonsClause = $this->getReasonsClause();
        $statement = "DELETE FROM blockchain_restricted_addresses WHERE $reasonsClause AND address = ?";
        $values = [ $address ];

        $query = new Custom();
        $query->query($statement, $values);

        return (bool) $this->client->request($query);
    }

    /**
     * Delete a Restriction to the database by reason.
     * @param string $reason - reason to clear all entries for.
     * @return bool true if deleted.
     */
    public function deleteByReason(string $reason): bool
    {
        $statement = "DELETE FROM blockchain_restricted_addresses WHERE reason = ?";
        $values = [ $reason ];

        $query = new Custom();
        $query->query($statement, $values);

        return (bool) $this->client->request($query);
    }

    /**
     * Gets reasons clause for query that permits any reason.
     * @return string reasons clause.
     */
    private function getReasonsClause(): string
    {
        return 'reason IN ('.implode(', ', array_map(function ($reason) {
            return "'$reason'";
        }, Constants::ALLOWED_REASONS)).')';
    }
}
