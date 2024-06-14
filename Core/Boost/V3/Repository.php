<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3;

use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Enums\BoostTargetAudiences;
use Minds\Core\Boost\V3\Exceptions\BoostNotFoundException;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOException;
use Selective\Database\Connection;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;

class Repository extends AbstractRepository
{
    public const TABLE_NAME = 'boosts';

    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        ... $args
    ) {
        $this->entitiesBuilder ??= Di::_()->get("EntitiesBuilder");

        parent::__construct(...$args);
    }

    /**
     * @return bool
     */
    public function createBoost(Boost $boost): bool
    {
        $query = $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'tenant_id' => new RawExp(':tenant_id'),
                'guid' => new RawExp(':guid'),
                'owner_guid' => new RawExp(':owner_guid'),
                'entity_guid' => new RawExp(':entity_guid'),
                'target_suitability' => new RawExp(':target_suitability'),
                'target_platform_web' => new RawExp(':target_platform_web'),
                'target_platform_android' => new RawExp(':target_platform_android'),
                'target_platform_ios' => new RawExp(':target_platform_ios'),
                'target_location' => new RawExp(':target_location'),
                'goal' => new RawExp(':goal'),
                'goal_button_text' => new RawExp(':goal_button_text'),
                'goal_button_url' => new RawExp(':goal_button_url'),
                'payment_method' => new RawExp(':payment_method'),
                'payment_amount' => new RawExp(':payment_amount'),
                'payment_tx_id' => new RawExp(':payment_tx_id'),
                'payment_guid' => new RawExp(':payment_guid'),
                'daily_bid' => new RawExp(':daily_bid'),
                'duration_days' => new RawExp(':duration_days'),
                'status' => new RawExp(':status'),
                'created_timestamp' => new RawExp(':created_timestamp'),
                'approved_timestamp' => new RawExp(':approved_timestamp'),
                'updated_timestamp' => new RawExp(':updated_timestamp'),
            ]);

        $createdTimestamp = $boost->getCreatedTimestamp() ?
            date("c", $boost->getCreatedTimestamp()) :
            date('c', time());

        $approvedTimestamp = $boost->getApprovedTimestamp() ?
            date("c", $boost->getApprovedTimestamp()) :
            null;

        $updatedTimestamp = $boost->getUpdatedTimestamp() ?
            date("c", $boost->getUpdatedTimestamp()) :
            null;

        $values = [
            'tenant_id' => $this->getTenantId(),
            'guid' => $boost->getGuid(),
            'owner_guid' => $boost->getOwnerGuid(),
            'entity_guid' => $boost->getEntityGuid(),
            'target_suitability' => $boost->getTargetSuitability(),
            'target_platform_web' => $boost->getTargetPlatformWeb(),
            'target_platform_android' => $boost->getTargetPlatformAndroid(),
            'target_platform_ios' => $boost->getTargetPlatformIos(),
            'target_location' => $boost->getTargetLocation(),
            'goal' => $boost->getGoal(),
            'goal_button_text' => $boost->getGoalButtonText(),
            'goal_button_url' => $boost->getGoalButtonUrl(),
            'payment_method' => $boost->getPaymentMethod(),
            'payment_amount' => $boost->getPaymentAmount(),
            'payment_tx_id' => $boost->getPaymentTxId(),
            'payment_guid' => $boost->getPaymentGuid(),
            'created_timestamp' => $createdTimestamp,
            'approved_timestamp' => $approvedTimestamp,
            'updated_timestamp' => $updatedTimestamp,
            'daily_bid' => $boost->getDailyBid(),
            'duration_days' => $boost->getDurationDays(),
            'status' => $boost->getStatus() ?? BoostStatus::PENDING,
        ];

        $statement = $query->prepare();
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        return $statement->execute();
    }

    /**
     * @return iterable<Boost>
     */
    public function getBoosts(
        int $limit = 12,
        int $offset = 0,
        ?int $targetStatus = null,
        bool $forApprovalQueue = false,
        ?string $targetUserGuid = null,
        bool $orderByRanking = false,
        ?int $targetAudience = null,
        ?int $targetLocation = null,
        ?string $entityGuid = null,
        ?int $paymentMethod = null,
        ?User $loggedInUser = null,
        bool &$hasNext = false
    ): iterable {
        $query = $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->columns([
                'boosts.*'
            ])
            ->where('boosts.tenant_id', Operator::EQ, new RawExp(':tenant_id'));
        
        $values = [
            'tenant_id' => $this->getTenantId(),
        ];

        if ($targetStatus) {
            $statusWhereClause = "(status = :status";
            $values['status'] = $targetStatus;

            if ($targetStatus !== BoostStatus::COMPLETED) {
                if (!$forApprovalQueue || $targetStatus !== BoostStatus::PENDING) {
                    $statusWhereClause .= " AND (approved_timestamp IS NULL OR :expired_timestamp < TIMESTAMPADD(DAY, duration_days, approved_timestamp))";
                    $values['expired_timestamp'] = date('c', time());
                }
            } else {
                $statusWhereClause .= " OR (approved_timestamp IS NOT NULL AND :expired_timestamp >= TIMESTAMPADD(DAY, duration_days, approved_timestamp))";
                $values['expired_timestamp'] = date('c', time());
            }

            $statusWhereClause .= ")";

            $query->whereRaw($statusWhereClause);
        }

        if (!$forApprovalQueue && $targetUserGuid) {
            $query->where('owner_guid', Operator::EQ, new RawExp(':owner_guid'));
            $values['owner_guid'] = $targetUserGuid;
        }

        if ($targetLocation) {
            $query->where('target_location', Operator::EQ, new RawExp(':target_location'));
            $values['target_location'] = $targetLocation;
        }

        if ($paymentMethod) {
            $query->where('payment_method', Operator::EQ, new RawExp(':payment_method'));
            $values['payment_method'] = $paymentMethod;
        }

        // if audience is safe, we want safe only, else we want all audiences.
        // if this is for the approval queue, we want admins to be able to filter between options.
        if ($targetAudience === BoostTargetAudiences::SAFE || $forApprovalQueue) {
            $query->where('target_suitability', Operator::EQ, new RawExp(':target_suitability'));
            $values['target_suitability'] = $targetAudience;
        }

        if ($entityGuid) {
            $query->where('entity_guid', Operator::EQ, new RawExp(':entity_guid'));
            $values['entity_guid'] = $entityGuid;
        }

        /**
         * Hide entities if a user has said they don't want to see them
         */
        if (!$forApprovalQueue && $loggedInUser) {
            $query->leftJoinRaw('entities_hidden', 'boosts.entity_guid = entities_hidden.entity_guid AND entities_hidden.user_guid = :user_guid');
            $values['user_guid'] = $loggedInUser->getGuid();

            $query->where('entities_hidden.entity_guid', Operator::IS, null);
        }

        $query->orderBy('created_timestamp DESC', 'updated_timestamp DESC', 'approved_timestamp DESC');

        if ($forApprovalQueue) {
            $query->orderBy('created_timestamp ASC');
        }


        if ($orderByRanking) {
            $query->join('boost_rankings', 'boosts.guid', Operator::EQ, 'boost_rankings.guid');

            $orderByRankingAudience = 'ranking_safe';
            if ($targetAudience === BoostTargetAudiences::CONTROVERSIAL) {
                $orderByRankingAudience = 'ranking_open';
            }

            $query->orderBy("boost_rankings.$orderByRankingAudience DESC");
        }

        /**
         * Joins with the boost_summaries table to get total views
         * Can be expanded later to get other aggregated statistics
         */
        if ($targetUserGuid) {
            $query->leftJoin(
                fn (SelectQuery $subquery) => $subquery
                ->from('boost_summaries')
                ->columns([
                    'guid',
                    'total_views' => new RawExp('SUM(views)'),
                    'total_clicks' => new RawExp('SUM(clicks)'),
                ])
                ->groupBy('guid')
                ->alias('summary'),
                'boosts.guid',
                Operator::EQ,
                'summary.guid'
            );

            $query->columns([
                'summary.total_views',
                'summary.total_clicks',
            ]);
        }

        $query->limit($limit + 1)
            ->offset($offset);

        $statement = $query->prepare();
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();

        $hasNext = $statement->rowCount() === $limit + 1;

        $i = 0;
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $boostData) {
            if (++$i > $limit) {
                break;
            }

            $entity = $i <= 12 ? $this->entitiesBuilder->single($boostData['entity_guid']) : null;

            yield (
                new Boost(
                    entityGuid: (string) $boostData['entity_guid'],
                    targetLocation: (int) $boostData['target_location'],
                    targetSuitability: (int) $boostData['target_suitability'],
                    targetPlatformWeb: isset($boostData['target_platform_web']) ? (bool) $boostData['target_platform_web'] : true,
                    targetPlatformAndroid: isset($boostData['target_platform_android']) ? (bool) $boostData['target_platform_android'] : true,
                    targetPlatformIos: isset($boostData['target_platform_ios']) ? (bool) $boostData['target_platform_ios'] : true,
                    goal: isset($boostData['goal']) ? (int) $boostData['goal'] : null,
                    goalButtonText: isset($boostData['goal_button_text']) ? (int) $boostData['goal_button_text'] : null,
                    goalButtonUrl: isset($boostData['goal_button_url']) ? (string) $boostData['goal_button_url'] : null,
                    paymentMethod: (int) $boostData['payment_method'],
                    paymentAmount: (float) $boostData['payment_amount'],
                    dailyBid: (int) $boostData['daily_bid'],
                    durationDays: (int) $boostData['duration_days'],
                    status: (int) $boostData['status'],
                    rejectionReason: (int) $boostData['reason'] ?: null,
                    createdTimestamp: strtotime($boostData['created_timestamp']),
                    paymentTxId: $boostData['payment_tx_id'],
                    updatedTimestamp:  isset($boostData['updated_timestamp']) ? strtotime($boostData['updated_timestamp']) : null,
                    approvedTimestamp: isset($boostData['approved_timestamp']) ? strtotime($boostData['approved_timestamp']) : null,
                    summaryViewsDelivered: (int) ($boostData['total_views'] ?? 0),
                    summaryClicksDelivered: (int) ($boostData['total_clicks'] ?? 0),
                    paymentGuid: (int) $boostData['payment_guid'] ?: null
                )
            )
                ->setGuid($boostData['guid'])
                ->setOwnerGuid($boostData['owner_guid'])
                ->setEntity($entity);
        }
    }

    /**
     * Get a single Boost by GUID. Will link with summaries table.
     * @param string $boostGuid - guid to get the Boost for.
     * @return Boost requested boost.
     * @throws BoostNotFoundException when no matching Boost is found.
     */
    public function getBoostByGuid(string $boostGuid): Boost
    {
        $values = [
            'guid' => $boostGuid,
            'tenant_id' => $this->getTenantId(),
        ];

        $query = $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->columns([
                'boosts.*',
                'summary.total_views'
            ])
            ->leftJoin(
                fn (SelectQuery $subquery) => $subquery
                ->from('boost_summaries')
                ->columns([
                    'guid',
                    'total_views' => new RawExp('SUM(views)'),
                    'total_clicks' => new RawExp('SUM(clicks)'),
                ])
                ->groupBy('guid')
                ->alias('summary'),
                'boosts.guid',
                Operator::EQ,
                'summary.guid'
            )
            ->where('boosts.guid', Operator::EQ, new RawExp(':guid'))
            ->where('boosts.tenant_id', Operator::EQ, new RawExp(':tenant_id'));

        $statement = $query->prepare();
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();

        if ($statement->rowCount() === 0) {
            throw new BoostNotFoundException();
        }

        $boostData = $statement->fetch(PDO::FETCH_ASSOC);
        $entity = $this->entitiesBuilder->single($boostData['entity_guid']);
        return (
            new Boost(
                entityGuid: (string) $boostData['entity_guid'],
                targetLocation: (int) $boostData['target_location'],
                targetSuitability: (int) $boostData['target_suitability'],
                targetPlatformWeb: isset($boostData['target_platform_web']) ? (bool) $boostData['target_platform_web'] : true,
                targetPlatformAndroid: isset($boostData['target_platform_android']) ? (bool) $boostData['target_platform_android'] : true,
                targetPlatformIos: isset($boostData['target_platform_ios']) ? (bool) $boostData['target_platform_ios'] : true,
                goal: isset($boostData['goal']) ? (int) $boostData['goal'] : null,
                goalButtonText: isset($boostData['goal_button_text']) ? (int) $boostData['goal_button_text'] : null,
                goalButtonUrl: isset($boostData['goal_button_url']) ? (string) $boostData['goal_button_url'] : null,
                paymentMethod: (int) $boostData['payment_method'],
                paymentAmount: (float) $boostData['payment_amount'],
                dailyBid: (float) $boostData['daily_bid'],
                durationDays: (int) $boostData['duration_days'],
                status: (int) $boostData['status'],
                rejectionReason: isset($boostData['reason']) ? (int) $boostData['reason'] : null,
                createdTimestamp: strtotime($boostData['created_timestamp']),
                paymentTxId: $boostData['payment_tx_id'],
                paymentGuid: (int) $boostData['payment_guid'] ?: null,
                updatedTimestamp: isset($boostData['updated_timestamp']) ? strtotime($boostData['updated_timestamp']) : null,
                approvedTimestamp: isset($boostData['approved_timestamp']) ? strtotime($boostData['approved_timestamp']) : null,
                summaryViewsDelivered: (int) $boostData['total_views'],
                summaryClicksDelivered: (int) ($boostData['total_clicks'] ?? 0),
            )
        )
            ->setGuid((string) $boostData['guid'])
            ->setOwnerGuid((string) $boostData['owner_guid'])
            ->setEntity($entity);
    }

    public function approveBoost(string $boostGuid, ?string $adminGuid): bool
    {
        $query = $this->mysqlClientWriterHandler->update()
            ->table(self::TABLE_NAME)
            ->set([
                'status' => new RawExp(':status'),
                'approved_timestamp' => new RawExp(':approved_timestamp'),
                'updated_timestamp' => new RawExp(':updated_timestamp'),
                'admin_guid' => new RawExp(':admin_guid'),
            ])
            ->where('guid', Operator::EQ, new RawExp(':guid'))
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'));

        $values = [
            'status' => BoostStatus::APPROVED,
            'approved_timestamp' => date('c', time()),
            'updated_timestamp' => date('c', time()),
            'guid' => $boostGuid,
            'admin_guid' => $adminGuid,
            'tenant_id' => $this->getTenantId(),
        ];

        $statement = $query->prepare();
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        return $statement->execute();
    }

    public function rejectBoost(string $boostGuid, int $reasonCode): bool
    {
        $query = $this->mysqlClientWriterHandler->update()
            ->table(self::TABLE_NAME)
            ->set([
                'status' => new RawExp(':status'),
                'updated_timestamp' => new RawExp(':updated_timestamp'),
                'reason' => new RawExp(':reason'),
            ])
            ->where('guid', Operator::EQ, new RawExp(':guid'))
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'));

        $values = [
            'status' => BoostStatus::REJECTED,
            'updated_timestamp' => date('c', time()),
            'reason' => $reasonCode,
            'guid' => $boostGuid,
            'tenant_id' => $this->getTenantId(),
        ];

        $statement = $query->prepare();
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        return $statement->execute();
    }

    public function cancelBoost(string $boostGuid, string $userGuid): bool
    {
        $query = $this->mysqlClientWriterHandler->update()
            ->table(self::TABLE_NAME)
            ->set([
                'status' => new RawExp(':status'),
                'updated_timestamp' => new RawExp(':updated_timestamp'),
            ])
            ->where('guid', Operator::EQ, new RawExp(':guid'))
            ->where('owner_guid', Operator::EQ, new RawExp(':owner_guid'))
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'));

        $values = [
            'status' => BoostStatus::CANCELLED,
            'updated_timestamp' => date('c', time()),
            'guid' => $boostGuid,
            'owner_guid' => $userGuid,
            'tenant_id' => $this->getTenantId(),
        ];

        $statement = $query->prepare();
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        return $statement->execute();
    }

    public function updateStatus(string $boostGuid, int $status): bool
    {
        $isCompleted = $status === BoostStatus::COMPLETED;

        $statement = $this->mysqlClientWriterHandler->update()
            ->table('boosts')
            ->set([
                'status' => new RawExp(':status'),
                'updated_timestamp' => $isCompleted ? new RawExp('updated_timestamp') : new RawExp(':timestamp'),
                'completed_timestamp' => !$isCompleted ? new RawExp('completed_timestamp') : new RawExp('TIMESTAMPADD(DAY, duration_days, updated_timestamp)'),
            ])
            ->where('guid', Operator::EQ, new RawExp(':guid'))
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->prepare();

        $values = [
            'status' => $status,
            'guid' => $boostGuid,
            'tenant_id' => $this->getTenantId(),
        ];

        if (!$isCompleted) {
            $values['timestamp'] = date('c', time());
        }

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        return $statement->execute();
    }

    /**
     * Force reject boosts in given statuses, by entity guid.
     * @param string $entityGuid - entity guid for which to force boost status.
     * @param int $reason - reason to be set as reject reason on update.
     * @param array $statuses - array of statuses to update status for.
     * @return bool true on success.
     */
    public function forceRejectByEntityGuid(
        string $entityGuid,
        int $reason,
        array $statuses = [BoostStatus::APPROVED, BoostStatus::PENDING]
    ): bool {
        $query = $this->mysqlClientWriterHandler->update()
            ->table('boosts')
            ->set([
                'status' => new RawExp(':status'),
                'updated_timestamp' => new RawExp(':updated_timestamp'),
                'reason' => new RawExp(':reason'),
            ])
            ->where('entity_guid', Operator::EQ, new RawExp(':entity_guid'))
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'));

        if (count($statuses)) {
            $query->where(new RawExp("(status = " . implode(' OR status = ', $statuses) . ")"));
        }

        $values = [
            'status' => BoostStatus::REJECTED,
            'updated_timestamp' => date('c', time()),
            'reason' => $reason,
            'entity_guid' => $entityGuid,
            'tenant_id' => $this->getTenantId(),
        ];

        $statement = $query->prepare();
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        return $statement->execute();
    }

    /**
     * Get admin stats.
     * @param int $targetStatus - target status to get stats for.
     * @param int|null $targetLocation - target location to get stats for.
     * @param int|null $paymentMethod - payment method to get stats for.
     * @return array key value array with admin stats.
     */
    public function getAdminStats(
        int $targetStatus = BoostStatus::PENDING,
        ?int $targetLocation = null,
        ?int $paymentMethod = null,
    ): array {
        $query = $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->columns([
                'safe_count' => new RawExp('SUM(CASE WHEN boosts.target_suitability = :safe_audience THEN 1 ELSE 0 END)'),
                'controversial_count' => new RawExp('SUM(CASE WHEN boosts.target_suitability = :controversial_audience THEN 1 ELSE 0 END)'),
            ])
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'));
    
        $values = [
            'tenant_id' => $this->getTenantId(),
        ];

        $statusWhereClause = "(status = :status";
        $values['status'] = $targetStatus;

        if ($targetStatus !== BoostStatus::COMPLETED) {
            if ($targetStatus !== BoostStatus::PENDING) {
                $statusWhereClause .= " AND (approved_timestamp IS NULL OR :expired_timestamp < TIMESTAMPADD(DAY, duration_days, approved_timestamp))";
                $values['expired_timestamp'] = date('c', time());
            }
        } else {
            $statusWhereClause .= " OR (approved_timestamp IS NOT NULL AND :expired_timestamp >= TIMESTAMPADD(DAY, duration_days, approved_timestamp))";
            $values['expired_timestamp'] = date('c', time());
        }

        $statusWhereClause .= ")";
        $query->where(new RawExp($statusWhereClause));

        if ($targetLocation) {
            $query->where('target_location', Operator::EQ, new RawExp(':target_location'));
            $values['target_location'] = $targetLocation;
        }

        if ($paymentMethod) {
            $query->where('payment_method', Operator::EQ, new RawExp(':payment_method'));
            $values['payment_method'] = $paymentMethod;
        }

        $values['safe_audience'] = BoostTargetAudiences::SAFE;
        $values['controversial_audience'] = BoostTargetAudiences::CONTROVERSIAL;

        $statement = $query->prepare();
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @return iterable<Boost>|null
     */
    public function getExpiredApprovedBoosts(): ?iterable
    {
        $query = $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->where('status', Operator::EQ, new RawExp(':status'))
            ->where(new RawExp(':expired_timestamp > TIMESTAMPADD(DAY, duration_days, approved_timestamp)'))
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'));

        $values = [
            "status" => BoostStatus::APPROVED,
            "expired_timestamp" => date('c', time()),
            'tenant_id' => $this->getTenantId(),
        ];
    
        $statement = $query->prepare();
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();
        if ($statement->rowCount() === 0) {
            return null;
        }

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $boostData) {
            yield (new Boost(
                entityGuid: (string) $boostData['entity_guid'],
                targetLocation: (int) $boostData['target_location'],
                targetSuitability: (int) $boostData['target_suitability'],
                paymentMethod: (int) $boostData['payment_method'],
                paymentAmount: (float) $boostData['payment_amount'],
                dailyBid: (int) $boostData['daily_bid'],
                durationDays: (int) $boostData['duration_days'],
                goal: isset($boostData['goal']) ? (int) $boostData['goal'] : null,
                goalButtonText: isset($boostData['goal_button_text']) ? (int) $boostData['goal_button_text'] : null,
                goalButtonUrl: isset($boostData['goal_button_url']) ? (string) $boostData['goal_button_url'] : null,
                status: (int) $boostData['status'],
                createdTimestamp: strtotime($boostData['created_timestamp']),
                paymentTxId: $boostData['payment_tx_id'],
                updatedTimestamp: isset($boostData['updated_timestamp']) ? strtotime($boostData['updated_timestamp']) : null,
                approvedTimestamp: isset($boostData['approved_timestamp']) ? strtotime($boostData['approved_timestamp']) : null,
                targetPlatformWeb: isset($boostData['target_platform_web']) ? (bool) $boostData['target_platform_web'] : true,
                targetPlatformAndroid: isset($boostData['target_platform_android']) ? (bool) $boostData['target_platform_android'] : true,
                targetPlatformIos: isset($boostData['target_platform_ios']) ? (bool) $boostData['target_platform_ios'] : true,
                tenantId: $boostData['tenant_id'] ?? -1,
            ))
                ->setGuid($boostData['guid'])
                ->setOwnerGuid($boostData['owner_guid']);
        }
    }

    /**
     * Get a count of a users last boosts, matching one of a mixed array of statuses.
     * @param string $targetUserGuid - target user to get statuses for.
     * @param array $statuses - statuses to count.
     * @param int $limit - limit of the max amount to count.
     * @return array array with format `status => count`.
     */
    public function getBoostStatusCounts(
        string $targetUserGuid = null,
        array $statuses,
        int $limit = 12,
    ): array {
        $values = [
            'tenant_id' => $this->getTenantId(),
        ];

        $values['owner_guid'] = $targetUserGuid;

        $query = $this->mysqlClientReaderHandler->select()
            ->columns([
                'status',
                'statusCount' => new RawExp('count(status)'),
            ])
            ->from(
                fn (SelectQuery $subquery) => $subquery
                ->columns([
                    'status'
                ])
                ->from(self::TABLE_NAME)
                ->where('owner_guid', Operator::EQ, new RawExp(':owner_guid'))
                ->where(new RawExp("(status = " . implode(' OR status = ', $statuses) . ")"))
                ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
                ->orderBy('updated_timestamp DESC')
                ->limit($limit)
                ->alias('boostsStatuses')
            )
            ->groupBy('status');



        $statement = $query->prepare();
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();
        $countsResult = $statement->fetchAll(PDO::FETCH_ASSOC);

        $formattedResult = [];
        foreach ($countsResult as $countItem) {
            $formattedResult[$countItem['status']] = $countItem['statusCount'];
        }

        return $formattedResult;
    }

    private function getTenantId(): int
    {
        return $this->config->get('tenant_id') ?: -1;
    }
}
