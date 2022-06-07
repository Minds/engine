<?php

namespace Minds\Core\AccountQuality;

use Minds\Core\AccountQuality\Models\UserQualityScore;
use Minds\Core\Data\Cassandra\Client as CassandraClient;
use Minds\Core\Data\Cassandra\Prepared\Custom as CustomQuery;
use Minds\Core\Di\Di;

/**
 * Responsible to fetch the data from the relevant data sources
 */
class Repository implements RepositoryInterface
{
    private const TABLE_NAME = "user_quality_scores";

    public function __construct(
        private ?CassandraClient $cassandraClient = null
    ) {
        $this->cassandraClient = $this->cassandraClient ?? Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * Retrieves the account quality score based on the userId provided
     * @param string $userId
     * @return UserQualityScore
     */
    public function getAccountQualityScore(string $userId): UserQualityScore
    {
        $statement = "SELECT score, category
            FROM
                " . self::TABLE_NAME . "
            WHERE
                user_id = ?
            LIMIT 1";

        $query = $this->prepareQuery($statement, [$userId]);

        $results = $this->cassandraClient->request($query);
        $entry = $results ? $results->first() : [];
        return (new UserQualityScore())
                ->setScore((float) ($entry["score"] ?? 0))
                ->setCategory($entry["category"] ?? 'Unknown');
    }

    /**
     * Returns a Cassandra prepared statement using the query and values provided
     * @param string $statement
     * @param array $values The values for the parameters in the query statement
     * @return CustomQuery
     */
    private function prepareQuery(string $statement, array $values): CustomQuery
    {
        $query = new CustomQuery();
        $query->query($statement, $values);

        return $query;
    }
}
