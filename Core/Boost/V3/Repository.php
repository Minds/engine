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
        $query = "INSERT INTO boosts (guid, owner_guid, entity_guid, target_suitability, target_location, payment_method, payment_amount, payment_tx_id, daily_bid, duration_days, status)
                    VALUES (:guid, :owner_guid, :entity_guid, :target_suitability, :target_location, :payment_method, :payment_amount, :payment_tx_id, :daily_bid, :duration_days, :status)";
        $values = [
            'guid' => $boost->getGuid(),
            'owner_guid' => $boost->getOwnerGuid(),
            'entity_guid' => $boost->getEntityGuid(),
            'target_suitability' => $boost->getTargetSuitability(),
            'target_location' => $boost->getTargetLocation(),
            'payment_method' => $boost->getPaymentMethod(),
            'payment_amount' => $boost->getPaymentAmount(),
            'payment_tx_id' => $boost->getPaymentTxId(),
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
     * @param int $targetAudience
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
        int $targetAudience = BoostTargetAudiences::SAFE,
        int $targetLocation = null,
        ?User $loggedInUser = null,
        bool &$hasNext = false
    ): Iterator {
        $values = [];
        $whereClauses = [];

        if ($targetStatus) {
            $whereClauses[] = "status = :status";
            $values['status'] = $targetStatus;
        }

        if (!$forApprovalQueue && $targetUserGuid) {
            $whereClauses[] = "owner_guid = :owner_guid";
            $values['owner_guid'] = $targetUserGuid;
        }

        if ($targetLocation) {
            $whereClauses[] = "target_location = :target_location";
            $values['target_location'] = $targetLocation;
        }

        if ($targetAudience) {
            $whereClauses[] = "target_suitability = :target_suitability";
            $values['target_suitability'] = $targetAudience;
        }

        $hiddenEntitiesJoin = "";

        /**
         * Hide entities if a user has aid they don't want to see them
         */
        if ($loggedInUser) {
            $hiddenEntitiesJoin = " LEFT JOIN entities_hidden
                ON boosts.entity_guid = entities_hidden.entity_guid
                AND entities_hidden.user_guid = :user_guid";
            $values['user_guid'] = $loggedInUser->getGuid();

            $whereClauses[] = 'entities_hidden.entity_guid IS NULL';
        }

        $orderByRankingJoin = "";
        $orderByClause = "";
        if ($orderByRanking) {
            $orderByRankingJoin = " LEFT JOIN boost_rankings ON boosts.guid = boost_rankings.guid";

            $orderByRankingAudience = 'ranking_safe';
            if ($targetAudience === BoostTargetAudiences::CONTROVERSIAL) {
                $orderByRankingAudience = 'ranking_open';
            }

            $orderByClause = " ORDER BY boost_rankings.$orderByRankingAudience DESC, boosts.approved_timestamp ASC";
        }

        $whereClause = '';
        if (count($whereClauses)) {
            $whereClause = 'WHERE '.implode(' AND ', $whereClauses);
        }

        $query = "SELECT boosts.* FROM boosts $hiddenEntitiesJoin $orderByRankingJoin $whereClause $orderByClause LIMIT :offset, :limit";
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
            $entity = $this->entitiesBuilder->single($boostData['entity_guid']);
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
                    createdTimestamp: strtotime($boostData['created_timestamp']),
                    paymentTxId: $boostData['payment_tx_id'],
                    updatedTimestamp:  isset($boostData['updated_timestamp']) ? strtotime($boostData['updated_timestamp']) : null,
                    approvedTimestamp: isset($boostData['approved_timestamp']) ? strtotime($boostData['approved_timestamp']) : null
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

    public function approveBoost(string $boostGuid): bool
    {
        $query = "UPDATE boosts SET status = :status, approved_timestamp = :approved_timestamp, updated_timestamp = :updated_timestamp WHERE guid = :guid";
        $values = [
            'status' => BoostStatus::APPROVED,
            'approved_timestamp' => date('c', time()),
            'updated_timestamp' => date('c', time()),
            'guid' => $boostGuid
        ];

        $statement = $this->mysqlClientWriter->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        return $statement->execute();
    }

    public function rejectBoost(string $boostGuid): bool
    {
        $query = "UPDATE boosts SET status = :status, updated_timestamp = :updated_timestamp WHERE guid = :guid";
        $values = [
            'status' => BoostStatus::REJECTED,
            'updated_timestamp' => date('c', time()),
            'guid' => $boostGuid
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
}
