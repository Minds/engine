<?php
declare(strict_types=1);

namespace Minds\Core\Feeds\ClusteredRecommendations;

use Generator;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\Elastic\ScoredGuid;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOStatement;

class MySQLRepository implements RepositoryInterface
{
    private const SEEN_ENTITIES_WEIGHT = 0.01;
    private const DEFAULT_RECS_USER_ID = 0;

    private PDO $mysqlClient;

    private ?User $user = null;

    /**
     * @param MySQLClient|null $mysqlHandler
     * @param MindsConfig|null $mindsConfig
     * @throws ServerErrorException
     */
    public function __construct(
        private ?MySQLClient $mysqlHandler = null,
        private ?MindsConfig $mindsConfig = null
    ) {
        $this->mysqlHandler ??= Di::_()->get("Database\MySQL\Client");

        $this->mysqlClient = $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_REPLICA);

        $this->mindsConfig ??= Di::_()->get("Config");
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
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
        $statement = $this->buildQuery($limit, $pseudoId, $demote);
        $statement->execute();

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            yield (new ScoredGuid())
                ->setGuid($row['activity_guid'])
                ->setType('activity')
                ->setScore($row['adjusted_score'])
                ->setOwnerGuid($row['channel_guid'])
                ->setTimestamp(0);
        }
    }

    /**
     * Builds the prepared MySQL statement to execute
     * @param int $limit
     * @param string $pseudoId
     * @param bool $demote
     * @return PDOStatement
     */
    private function buildQuery(int $limit, string $pseudoId, bool $demote): PDOStatement
    {
        $values = [];

        $query = "select
                        recs_activities.activity_guid, recs_activities.channel_guid";

        if ($demote) {
            $query .= ", IF(se.entity_guid IS NULL, recs_activities.score, recs_activities.score * :score_multiplier) as adjusted_score ";
            $values['score_multiplier'] = $this->mindsConfig->get('seen-entities-weight') ?? self::SEEN_ENTITIES_WEIGHT;
        } else {
            $query .= ", recs_activities.score as adjusted_score ";
        }
        $query .= " from
                        minds.recommendations_user_cluster_map as recs_cluster
                        inner join minds.recommendations_cluster_activity_map as recs_activities on
                            recs_cluster.cluster_id = recs_activities.cluster_id
                            and recs_cluster.user_id <> recs_activities.channel_guid";

        if ($demote) {
            $query .= " LEFT JOIN
                pseudo_seen_entities as se
                ON
                    se.pseudo_id = :pseudo_id AND se.entity_guid = recs_activities.activity_guid ";
            $values['pseudo_id'] = $pseudoId;
        }

        $query .= " where
                        user_id = :user_id or user_id = :default_recs_user_id
                    order by
                        user_id desc,
                        adjusted_score desc
                    limit :limit
                    ";
        $values['user_id'] = $this->user->getGuid();
        $values['default_recs_user_id'] = self::DEFAULT_RECS_USER_ID;
        $values['limit'] = $limit;

        $statement = $this->mysqlClient->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        return $statement;
    }
}
