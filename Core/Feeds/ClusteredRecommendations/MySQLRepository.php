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
use Selective\Database\Connection;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;

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
        private ?MindsConfig $mindsConfig = null,
        private ?Connection $mysqlReaderHandler = null
    ) {
        $this->mysqlHandler ??= Di::_()->get("Database\MySQL\Client");

        $this->mysqlClient = $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_REPLICA);
        $this->mysqlReaderHandler ??= new Connection($this->mysqlClient);

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
    public function getList(int $clusterId, int $limit, array $exclude = [], bool $demote = false, ?string $pseudoId = null, ?array $tags = null): Generator
    {
        $statement = $this->buildQuery($limit, $pseudoId, $demote, $tags);
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
     * @param array|null $tags
     * @return PDOStatement
     */
    private function buildQuery(int $limit, string $pseudoId, bool $demote, ?array $tags = null): PDOStatement
    {
        $values = [];

        $statement = $this->mysqlReaderHandler->select()
            ->columns([
                'recs_activities.activity_guid',
                'recs_activities.channel_guid',
                'adjusted_score' => new RawExp("CASE WHEN pseudo_seen_entities.entity_guid IS NULL THEN recs_activities.score ELSE recs_activities.score * :score_multiplier END")
            ])
            ->from(function (SelectQuery $subQuery) use (&$values, $tags, $pseudoId): void {
                $subQuery
                    ->columns([
                        'cluster_id' => new RawExp('COALESCE(user_cluster_id, tag_cluster_id, default_cluster_id)'),
                        'user_param.pseudo_id',
                        'user_param.user_id'
                    ])
                    ->from(function (SelectQuery $subQuery) use (&$values, $pseudoId): void {
                        $subQuery
                            ->columns([
                                'user_id' => new RawExp(":user_id"),
                                'pseudo_id' => new RawExp(":pseudo_id")
                            ])
                            ->alias('user_param');
                        $values['user_id'] = $this->user->getGuid();
                        $values['pseudo_id'] = $pseudoId;
                    })
                    ->leftJoinRaw(
                        function (SelectQuery $subQuery): void {
                            $subQuery
                                ->columns([
                                    'default_cluster_id' => 'cluster_id'
                                ])
                                ->from('recommendations_latest_user_clusters')
                                ->where('user_id', Operator::EQ, self::DEFAULT_RECS_USER_ID)
                                ->alias('default_cluster');
                        },
                        'true',
                    )
                    ->leftJoinRaw(
                        function (SelectQuery $subQuery) use (&$values, $tags): void {
                            $subQuery
                                ->columns([
                                    'tag_cluster_id' => 'cluster_id',
                                    'score' => new RawExp('SUM(CASE WHEN locate(interest_tag, :user_interest_tags) > 0 THEN relative_ratio ELSE -relative_ratio END)')
                                ])
                                ->from('recommendations_latest_cluster_tags')
                                ->groupBy('cluster_id')
                                ->having('score', Operator::GT, 0)
                                ->orderBy('score desc')
                                ->alias('tag_cluster');
                            $values['user_interest_tags'] = implode(' ', $tags ?? []);
                        },
                        'true'
                    )
                    ->leftJoin(
                        function (SelectQuery $subQuery): void {
                            $subQuery
                                ->columns([
                                    'user_cluster_id' => 'cluster_id',
                                    'user_id'
                                ])
                                ->from('recommendations_latest_user_clusters')
                                ->alias('user_cluster');
                        },
                        'user_cluster.user_id',
                        Operator::EQ,
                        'user_param.user_id'
                    )
                    ->alias('cluster');
            })
            ->joinRaw(
                new RawExp('recommendations_latest_cluster_activities as recs_activities'),
                "cluster.cluster_id = recs_activities.cluster_id AND (cluster.user_id IS NULL OR cluster.user_id <> recs_activities.channel_guid)"
            )
            ->leftJoinRaw(
                'pseudo_seen_entities',
                'pseudo_seen_entities.pseudo_id = cluster.pseudo_id and pseudo_seen_entities.entity_guid = recs_activities.activity_guid'
            )
            ->orderBy('adjusted_score desc')
            ->limit($limit)
            ->prepare();

        $values['score_multiplier'] = self::SEEN_ENTITIES_WEIGHT;

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);
        return $statement;
    }
}
