<?php

namespace Minds\Core\AccountQuality;

use Cassandra\Bigint;
use Minds\Core\Data\Cassandra\Client as CassandraClient;
use Minds\Core\Data\Cassandra\Prepared\Custom as CustomQuery;
use Minds\Core\Di\Di;

class Repository implements RepositoryInterface
{
    private const TABLE_NAME = "account_quality_scores";
    public function __construct(
        private ?CassandraClient $cassandraClient = null
    ) {
        $this->cassandraClient = $this->cassandraClient ?? Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * Retrieves the account quality score based on the userId provided
     * @param string $userId
     * @return float
     */
    public function getAccountQualityScore(string $userId): float
    {
        $statement = "SELECT score
            FROM
                " . self::TABLE_NAME . "
            WHERE
                user_guid = ?
            LIMIT 1";

        $query = $this->prepareQuery($statement, [new Bigint($userId)]);

        $results = $this->cassandraClient->request($query);
        return (float) $results->first()["score"];
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
