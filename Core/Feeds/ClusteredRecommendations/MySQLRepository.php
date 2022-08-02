<?php

namespace Minds\Core\Feeds\ClusteredRecommendations;

use Generator;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\Elastic\ScoredGuid;
use PDO;
use PDOStatement;

class MySQLRepository implements RepositoryInterface
{
    private const SEEN_ENTITIES_WEIGHT = 0.01;

    private PDO $mysqlClient;

    public function __construct(
        ?MySQLClient $mysqlClient = null,
        private ?MindsConfig $mindsConfig = null
    ) {
        $mysqlClient ??= Di::_()->get("Database\MySQL\Client");

        $this->mysqlClient = $mysqlClient?->getConnection(MySQLClient::CONNECTION_REPLICA);

        $this->mindsConfig ??= Di::_()->get("Config");
    }

    /**
     * @param int $clusterId
     * @param int $limit
     * @param array<int, string> $exclude
     * @param bool $demote
     * @param string|null $pseudoId
     * @return Generator
     */
    public function getList(int $clusterId, int $limit, array $exclude = [], bool $demote = false, ?string $pseudoId = null): Generator
    {
        ['preparedQuery' => $preparedQuery, 'values' => $values] = $this->buildQuery($clusterId, $limit, $pseudoId, $demote);
        $preparedQuery->execute($values);

        foreach ($preparedQuery as $row) {
            yield (new ScoredGuid())
                ->setGuid($row['entity_guid'])
                ->setType('activity')
                ->setScore($row['adjusted_score'])
                ->setOwnerGuid($row['entity_owner_guid'])
                ->setTimestamp($row['time_created']);
        }
    }

    /**
     * Builds the prepared MySQL statement to execute
     * @param int $clusterId
     * @param int $limit
     * @param string $pseudoId
     * @param bool $demote
     * @return array{"preparedQuery": PDOStatement|false, "values": array<string, string>}
     */
    private function buildQuery(int $clusterId, int $limit, string $pseudoId, bool $demote): array
    {
        $values = [];

        $query = "SELECT
            cr.entity_guid, cr.entity_owner_guid, cr.time_created ";

        if ($demote) {
            $query .= ", IF(se.entity_guid IS NULL, cr.score, cr.score * :score_multiplier) as adjusted_score ";
            $values['score_multiplier'] = $this->mindsConfig->get('seen-entities-weight') ?? self::SEEN_ENTITIES_WEIGHT;
        } else {
            $query .= ", cr.score as adjusted_score ";
        }

        $query .= "FROM
            recommendations_clustered_recs as cr ";

        if ($demote) {
            $query .= "LEFT JOIN
                pseudo_seen_entities as se
                ON
                    se.pseudo_id = :pseudo_id AND se.entity_guid = cr.entity_guid ";
            $values['pseudo_id'] = $pseudoId;
        }

        $query .= "WHERE
            cluster_id = :cluster_id
        ORDER BY
            adjusted_score DESC
        LIMIT
            $limit";
        $values['cluster_id'] = 1;

        return [
            'preparedQuery' => $this->mysqlClient->prepare(
                $query
            ),
            'values' => $values
        ];
    }
}
