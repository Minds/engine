<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3;

use Iterator;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Enums\BoostTargetAudiences;
use Minds\Core\Boost\V3\Exceptions\BoostNotFoundException;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOException;

class Repository
{
    private PDO $mysqlClientReader;
    private PDO $mysqlClientWriter;

    /**
     * @param MySQLClient|null $mysqlHandler
     * @param EntitiesBuilder|null $entitiesBuilder
     * @throws ServerErrorException
     */
    public function __construct(
        private ?MySQLClient $mysqlHandler = null,
        private ?EntitiesBuilder $entitiesBuilder = null
    ) {
        $this->mysqlHandler ??= Di::_()->get("Database\MySQL\Client");
        $this->mysqlClientReader = $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_REPLICA);
        $this->mysqlClientWriter = $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_MASTER);

        $this->entitiesBuilder ??= Di::_()->get("EntitiesBuilder");
    }

    public function beginTransaction(): void
    {
        if ($this->mysqlClientWriter->inTransaction()) {
            throw new PDOException("Cannot initiate transaction. Previously initiated transaction still in progress.");
        }

        $this->mysqlClientWriter->beginTransaction();
    }

    public function rollbackTransaction(): void
    {
        if ($this->mysqlClientWriter->inTransaction()) {
            $this->mysqlClientWriter->rollBack();
        }
    }

    public function commitTransaction(): void
    {
        $this->mysqlClientWriter->commit();
    }

    /**
     * @param Boost $boost
     * @return bool
     */
    public function createBoost(Boost $boost): bool
    {
        $query = "INSERT INTO boosts (guid, owner_guid, entity_guid, target_suitability, target_location, payment_method, payment_amount, payment_tx_id, daily_bid, duration_days, status, created_timestamp, approved_timestamp, updated_timestamp)
                    VALUES (:guid, :owner_guid, :entity_guid, :target_suitability, :target_location, :payment_method, :payment_amount, :payment_tx_id, :daily_bid, :duration_days, :status, :created_timestamp, :approved_timestamp, :updated_timestamp)";

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
            'guid' => $boost->getGuid(),
            'owner_guid' => $boost->getOwnerGuid(),
            'entity_guid' => $boost->getEntityGuid(),
            'target_suitability' => $boost->getTargetSuitability(),
            'target_location' => $boost->getTargetLocation(),
            'payment_method' => $boost->getPaymentMethod(),
            'payment_amount' => $boost->getPaymentAmount(),
            'payment_tx_id' => $boost->getPaymentTxId(),
            'created_timestamp' => $createdTimestamp,
            'approved_timestamp' => $approvedTimestamp,
            'updated_timestamp' => $updatedTimestamp,
            'daily_bid' => $boost->getDailyBid(),
            'duration_days' => $boost->getDurationDays(),
            'status' => $boost->getStatus() ?? BoostStatus::PENDING,
        ];

        $statement = $this->mysqlClientWriter->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        return $statement->execute();
    }

    /**
     * @param int $limit
     * @param int $offset
     * @param int|null $targetStatus
     * @param bool $forApprovalQueue
     * @param string|null $targetUserGuid
     * @param bool $orderByRanking
     * @param int|null $targetAudience
     * @param int|null $targetLocation
     * @param int|null $paymentMethod
     * @param string|null $entityGuid
     * @param int|null $paymentMethod
     * @param User|null $loggedInUser
     * @param bool $hasNext
     * @return Iterator
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
    ): Iterator {
        $values = [];

        $selectColumns = [
            "boosts.*"
        ];
        $whereClauses = [];

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
            $whereClauses[] = $statusWhereClause;
        }

        if (!$forApprovalQueue && $targetUserGuid) {
            $whereClauses[] = "owner_guid = :owner_guid";
            $values['owner_guid'] = $targetUserGuid;
        }

        if ($targetLocation) {
            $whereClauses[] = "target_location = :target_location";
            $values['target_location'] = $targetLocation;
        }

        if ($paymentMethod) {
            $whereClauses[] = "payment_method = :payment_method";
            $values['payment_method'] = $paymentMethod;
        }

        // if audience is safe, we want safe only, else we want all audiences.
        // if this is for the approval queue, we want admins to be able to filter between options.
        if ($targetAudience === BoostTargetAudiences::SAFE || $forApprovalQueue) {
            $whereClauses[] = "target_suitability = :target_suitability";
            $values['target_suitability'] = $targetAudience;
        }

        if ($entityGuid) {
            $whereClauses[] = "entity_guid = :entity_guid";
            $values['entity_guid'] = $entityGuid;
        }

        $hiddenEntitiesJoin = "";

        /**
         * Hide entities if a user has aid they don't want to see them
         */
        if (!$forApprovalQueue && $loggedInUser) {
            $hiddenEntitiesJoin = " LEFT JOIN entities_hidden
                ON boosts.entity_guid = entities_hidden.entity_guid
                AND entities_hidden.user_guid = :user_guid";
            $values['user_guid'] = $loggedInUser->getGuid();

            $whereClauses[] = 'entities_hidden.entity_guid IS NULL';
        }

        $orderByRankingJoin = "";
        $orderByClause = " ORDER BY created_timestamp DESC, updated_timestamp DESC, approved_timestamp DESC";

        if ($forApprovalQueue) {
            $orderByClause = " ORDER BY created_timestamp ASC";
        }


        if ($orderByRanking) {
            $orderByRankingJoin = " INNER JOIN boost_rankings ON boosts.guid = boost_rankings.guid";

            $orderByRankingAudience = 'ranking_safe';
            if ($targetAudience === BoostTargetAudiences::CONTROVERSIAL) {
                $orderByRankingAudience = 'ranking_open';
            }

            $orderByClause = " ORDER BY boost_rankings.$orderByRankingAudience DESC";
        }

        /**
         * Joins with the boost_summaries table to get total views
         * Can be expanded later to get other aggregated statistics
         */
        $summariesJoin = "";
        if ($targetUserGuid) {
            $summariesJoin = " LEFT JOIN (
                    SELECT guid, SUM(views) as total_views FROM boost_summaries
                    GROUP BY 1
                ) summary
                ON boosts.guid=summary.guid";
            $selectColumns[] = "summary.total_views";
        }


        $whereClause = '';
        if (count($whereClauses)) {
            $whereClause = 'WHERE '.implode(' AND ', $whereClauses);
        }

        $selectColumnsStr = implode(',', $selectColumns);

        $query = "SELECT $selectColumnsStr FROM boosts $summariesJoin $hiddenEntitiesJoin $orderByRankingJoin $whereClause $orderByClause LIMIT :offset, :limit";
        $values['offset'] = $offset;
        $values['limit'] = $limit + 1;

        $statement = $this->mysqlClientReader->prepare($query);
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
                    entityGuid: $boostData['entity_guid'],
                    targetLocation: (int) $boostData['target_location'],
                    targetSuitability: (int) $boostData['target_suitability'],
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
                    summaryViewsDelivered: (int) $boostData['total_views'],
                )
            )
                ->setGuid($boostData['guid'])
                ->setOwnerGuid($boostData['owner_guid'])
                ->setEntity($entity);
        }
    }

    /**
     * @param string $boostGuid
     * @return Boost
     * @throws BoostNotFoundException
     */
    public function getBoostByGuid(string $boostGuid): Boost
    {
        $query = "SELECT * FROM boosts WHERE guid = :guid";
        $values = ['guid' => $boostGuid];

        $statement = $this->mysqlClientReader->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();

        if ($statement->rowCount() === 0) {
            throw new BoostNotFoundException();
        }

        $boostData = $statement->fetch(PDO::FETCH_ASSOC);
        $entity = $this->entitiesBuilder->single($boostData['entity_guid']);
        return (
            new Boost(
                entityGuid: $boostData['entity_guid'],
                targetLocation: (int) $boostData['target_location'],
                targetSuitability: (int) $boostData['target_suitability'],
                paymentMethod: (int) $boostData['payment_method'],
                paymentAmount: (float) $boostData['payment_amount'],
                dailyBid: (float) $boostData['daily_bid'],
                durationDays: (int) $boostData['duration_days'],
                status: (int) $boostData['status'],
                rejectionReason: isset($boostData['reason']) ? (int) $boostData['reason'] : null,
                createdTimestamp: strtotime($boostData['created_timestamp']),
                paymentTxId: $boostData['payment_tx_id'],
                updatedTimestamp: isset($boostData['updated_timestamp']) ? strtotime($boostData['updated_timestamp']) : null,
                approvedTimestamp: isset($boostData['approved_timestamp']) ? strtotime($boostData['approved_timestamp']) : null
            )
        )
            ->setGuid($boostData['guid'])
            ->setOwnerGuid($boostData['owner_guid'])
            ->setEntity($entity);
    }

    public function approveBoost(string $boostGuid, ?string $adminGuid): bool
    {
        $query = "UPDATE boosts SET status = :status, approved_timestamp = :approved_timestamp, updated_timestamp = :updated_timestamp, admin_guid = :admin_guid WHERE guid = :guid";
        $values = [
            'status' => BoostStatus::APPROVED,
            'approved_timestamp' => date('c', time()),
            'updated_timestamp' => date('c', time()),
            'guid' => $boostGuid,
            'admin_guid' => $adminGuid
        ];

        $statement = $this->mysqlClientWriter->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        return $statement->execute();
    }

    public function rejectBoost(string $boostGuid, int $reasonCode): bool
    {
        $query = "UPDATE boosts SET status = :status, updated_timestamp = :updated_timestamp, reason = :reason WHERE guid = :guid";
        $values = [
            'status' => BoostStatus::REJECTED,
            'updated_timestamp' => date('c', time()),
            'reason' => $reasonCode,
            'guid' => $boostGuid
        ];

        $statement = $this->mysqlClientWriter->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        return $statement->execute();
    }

    public function cancelBoost(string $boostGuid, string $userGuid): bool
    {
        $query = "UPDATE boosts SET status = :status, updated_timestamp = :updated_timestamp WHERE guid = :guid AND owner_guid = :owner_guid";
        $values = [
            'status' => BoostStatus::CANCELLED,
            'updated_timestamp' => date('c', time()),
            'guid' => $boostGuid,
            'owner_guid' => $userGuid,
        ];

        $statement = $this->mysqlClientWriter->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        return $statement->execute();
    }

    public function updateStatus(string $boostGuid, int $status): bool
    {
        $query = "UPDATE boosts SET status = :status, updated_timestamp = :updated_timestamp WHERE guid = :guid";
        $values = [
            'status' => $status,
            'updated_timestamp' => date('c', time()),
            'guid' => $boostGuid
        ];

        $statement = $this->mysqlClientWriter->prepare($query);
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
        $query = "UPDATE boosts
            SET status = :status,
                updated_timestamp = :updated_timestamp,
                reason = :reason
            WHERE entity_guid = :entity_guid";

        if (count($statuses)) {
            $query .= " AND (status = " . implode(' OR status = ', $statuses) . ")";
        }

        $values = [
            'status' => BoostStatus::REJECTED,
            'updated_timestamp' => date('c', time()),
            'reason' => $reason,
            'entity_guid' => $entityGuid,
        ];

        $statement = $this->mysqlClientWriter->prepare($query);
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
        $values = [];
        $whereClauses = [];

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
        $whereClauses[] = $statusWhereClause;

        if ($targetLocation) {
            $whereClauses[] = "target_location = :target_location";
            $values['target_location'] = $targetLocation;
        }

        if ($paymentMethod) {
            $whereClauses[] = "payment_method = :payment_method";
            $values['payment_method'] = $paymentMethod;
        }

        $whereClause = '';
        if (count($whereClauses)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereClauses);
        }

        $query = "SELECT
            SUM(CASE WHEN boosts.target_suitability = :safe_audience THEN 1 ELSE 0 END) as safe_count,
            SUM(CASE WHEN boosts.target_suitability = :controversial_audience THEN 1 ELSE 0 END) AS controversial_count
            FROM boosts $whereClause";

        $values['safe_audience'] = BoostTargetAudiences::SAFE;
        $values['controversial_audience'] = BoostTargetAudiences::CONTROVERSIAL;

        $statement = $this->mysqlClientReader->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @return iterable<Boost>|null
     */
    public function getExpiredApprovedBoosts(): ?iterable
    {
        $query = "SELECT * FROM boosts WHERE status = :status AND :expired_timestamp > TIMESTAMPADD(DAY, duration_days, approved_timestamp)";
        $values = [
            "status" => BoostStatus::APPROVED,
            "expired_timestamp" => date('c', time())
        ];
        $statement = $this->mysqlClientReader->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();
        if ($statement->rowCount() === 0) {
            return null;
        }

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $boostData) {
            yield (new Boost(
                entityGuid: $boostData['entity_guid'],
                targetLocation: (int) $boostData['target_location'],
                targetSuitability: (int) $boostData['target_suitability'],
                paymentMethod: (int) $boostData['payment_method'],
                paymentAmount: (float) $boostData['payment_amount'],
                dailyBid: (int) $boostData['daily_bid'],
                durationDays: (int) $boostData['duration_days'],
                status: (int) $boostData['status'],
                createdTimestamp: strtotime($boostData['created_timestamp']),
                paymentTxId: $boostData['payment_tx_id'],
                updatedTimestamp:  isset($boostData['updated_timestamp']) ? strtotime($boostData['updated_timestamp']) : null,
                approvedTimestamp: isset($boostData['approved_timestamp']) ? strtotime($boostData['approved_timestamp']) : null
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
        $values = [];
        $whereClauses = [];

        $whereClauses[] = "owner_guid = :owner_guid";
        $values['owner_guid'] = $targetUserGuid;

        $statusesString = "status = " . implode(' OR status = ', $statuses);
        $whereClauses[] = "($statusesString)";

        $whereClause = '';
        if (count($whereClauses)) {
            $whereClause = 'WHERE '.implode(' AND ', $whereClauses);
        }

        $query = "SELECT status, count(status) AS statusCount
            FROM
                (
                    SELECT status
                    FROM
                        boosts
                    $whereClause
                    ORDER BY
                        updated_timestamp DESC
                    LIMIT :limit
                ) as boostsStatuses
            GROUP BY status";

        $values['limit'] = $limit;

        $statement = $this->mysqlClientReader->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();
        $countsResult = $statement->fetchAll(PDO::FETCH_ASSOC);

        $formattedResult = [];
        foreach ($countsResult as $countItem) {
            $formattedResult[$countItem['status']] = $countItem['statusCount'];
        }

        return $formattedResult;
    }
}
