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
        $query = "INSERT INTO boosts (guid, owner_guid, entity_guid, target_suitability, target_location, payment_method, payment_amount, daily_bid, duration_days, status)
                    VALUES (:guid, :owner_guid, :entity_guid, :target_suitability, :target_location, :payment_method, :payment_amount, :daily_bid, :duration_days, :status)";
        $values = [
            'guid' => $boost->getGuid(),
            'owner_guid' => $boost->getOwnerGuid(),
            'entity_guid' => $boost->getEntityGuid(),
            'target_suitability' => $boost->getTargetSuitability(),
            'target_location' => $boost->getTargetLocation(),
            'payment_method' => $boost->getPaymentMethod(),
            'payment_amount' => $boost->getPaymentAmount(),
            'daily_bid' => $boost->getDailyBid(),
            'duration_days' => $boost->getDurationDays(),
            'status' => $boost->getStatus(),
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
        bool &$hasNext = false
    ): Iterator {
        $values = [];

        $statusClause = "";
        if ($targetStatus) {
            $statusClause = "status = :status";
            $values['status'] = $targetStatus;
        }

        $ownerClause = "";
        if (!$forApprovalQueue && $targetUserGuid) {
            $ownerClause = (empty($statusClause) ? "" : " AND ") . "owner_guid = :owner_guid";
            $values['owner_guid'] = $targetUserGuid;
        }

        $orderByRankingJoin = "";
        $orderByClause = "";
        if ($orderByRanking) {
            $orderByRankingJoin = " LEFT JOIN boost_rankings ON boosts.guid = boost_rankings.guid";

            $orderByRankingAudience = 'ranking_safe';
            if ($targetAudience === BoostTargetAudiences::OPEN) {
                $orderByRankingAudience = 'ranking_open';
            }

            $orderByClause = " ORDER BY boost_rankings.$orderByRankingAudience DESC, boost.approved_timestamp DESC";
        }

        $query = "SELECT * FROM boosts $orderByRankingJoin WHERE $statusClause $ownerClause $orderByClause LIMIT :offset, :limit";
        $values['offset'] = $offset;
        $values['limit'] = $limit + 1;

        $statement = $this->mysqlClientReader->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();

        $hasNext = $statement->rowCount() === $limit + 1;

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $boostData) {
            $entity = $this->entitiesBuilder->single($boostData['entity_guid']);
            yield (
                new Boost(
                    entityGuid: $boostData['entity_guid'],
                    targetLocation: $boostData['target_location'],
                    targetSuitability: $boostData['target_suitability'],
                    paymentMethod: $boostData['payment_method'],
                    paymentAmount: $boostData['payment_amount'],
                    dailyBid: $boostData['daily_bid'],
                    durationDays: $boostData['duration_days'],
                    status: $boostData['status'],
                    createdTimestamp: $boostData['created_timestamp'],
                    paymentTxId: $boostData['payment_tx_id'],
                    updatedTimestamp: $boostData['updated_timestamp'],
                    approvedTimestamp: $boostData['approved_timestamp']
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

        return (
            new Boost(
                entityGuid: $boostData['entity_guid'],
                targetLocation: $boostData['target_location'],
                targetSuitability: $boostData['target_suitability'],
                paymentMethod: $boostData['payment_method'],
                paymentAmount: $boostData['payment_amount'],
                dailyBid: $boostData['daily_bid'],
                durationDays: $boostData['duration_days'],
                status: $boostData['status'],
                createdTimestamp: $boostData['created_timestamp'],
                paymentTxId: $boostData['payment_tx_id'],
                updatedTimestamp: $boostData['updated_timestamp'],
                approvedTimestamp: $boostData['approved_timestamp']
            )
        )
            ->setGuid($boostData['guid'])
            ->setOwnerGuid($boostData['owner_guid']);
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
