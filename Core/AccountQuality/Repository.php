<?php

namespace Minds\Core\AccountQuality;

use _PHPStan_76800bfb5\Nette\Neon\Exception;
use Minds\Common\Repository\Response;
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

    public function getAccountQualityScores(?array $userIds = null): array
    {
        $statement = "SELECT user_guid, score
            FROM
                {self::TABLE_NAME}";

        if ($userIds) {

        }

        throw new Exception("method not implemented");
    }

    public function getAccountQualityScore(string $userId): int
    {
        $statement = "SELECT score
            FROM
                {self::TABLE_NAME}
            WHERE
                user_guid = ?
            LIMIT 1";

        $query = $this->prepareQuery($statement, [$userId]);

        $results = $this->cassandraClient->request($query);
        return $results->first()["score"];
    }

    /**
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
