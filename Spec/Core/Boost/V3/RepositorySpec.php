<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Boost\V3;

use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Boost\V3\Enums\BoostRejectionReason;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Enums\BoostTargetAudiences;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\Enums\BoostTargetSuitability;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Boost\V3\Repository;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\EntitiesBuilder;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Selective\Database\Connection;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\UpdateQuery;
use Spec\Minds\Common\Traits\CommonMatchers;

class RepositorySpec extends ObjectBehavior
{
    use CommonMatchers;

    private Collaborator $mysqlHandler;
    private Collaborator $mysqlClientReader;
    private Collaborator $mysqlClientWriter;
    private Collaborator $mysqlClientWriterHandler;
    private Collaborator $entitiesBuilder;

    /**
     * @param MySQLClient $mysqlHandler
     * @param PDO $mysqlClientReader
     * @param PDO $mysqlClientWriter
     * @param EntitiesBuilder $entitiesBuilder
     * @return void
     * @throws ServerErrorException
     */
    public function let(
        MySQLClient $mysqlHandler,
        PDO    $mysqlClientReader,
        PDO    $mysqlClientWriter,
        Connection $mysqlClientWriterHandler,
        EntitiesBuilder $entitiesBuilder
    ): void {
        $this->mysqlHandler = $mysqlHandler;

        $this->mysqlClientReader = $mysqlClientReader;
        $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_REPLICA)
            ->willReturn($this->mysqlClientReader);

        $this->mysqlClientWriter = $mysqlClientWriter;
        $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_MASTER)
            ->willReturn($this->mysqlClientWriter);

        $mysqlClientWriterHandler->getPdo()->willReturn($this->mysqlClientWriter);
        $this->mysqlClientWriterHandler = $mysqlClientWriterHandler;

        $this->entitiesBuilder = $entitiesBuilder;

        $this->beConstructedWith($this->mysqlHandler, $this->entitiesBuilder, $this->mysqlClientWriterHandler);
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(Repository::class);
    }

    public function it_should_create_boost(
        Boost $boost,
        PDOStatement $statement
    ): void {
        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $query = "INSERT INTO boosts (guid, owner_guid, entity_guid, target_suitability, target_location, goal, goal_button_text, goal_button_url, payment_method, payment_amount, payment_tx_id, daily_bid, duration_days, status, created_timestamp, approved_timestamp, updated_timestamp)
                    VALUES (:guid, :owner_guid, :entity_guid, :target_suitability, :target_location, :goal, :goal_button_text, :goal_button_url, :payment_method, :payment_amount, :payment_tx_id, :daily_bid, :duration_days, :status, :created_timestamp, :approved_timestamp, :updated_timestamp)";
        $this->mysqlClientWriter->prepare($query)
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $boost->getGuid()
            ->willReturn('1234');
        $boost->getOwnerGuid()
            ->willReturn('1235');
        $boost->getEntityGuid()
            ->willReturn('1236');
        $boost->getTargetSuitability()
            ->willReturn(BoostTargetSuitability::SAFE);
        $boost->getTargetLocation()
            ->willReturn(BoostTargetLocation::NEWSFEED);
        $boost->getGoal()
            ->willReturn(null);
        $boost->getGoalButtonText()
            ->willReturn(null);
        $boost->getGoalButtonUrl()
            ->willReturn(null);
        $boost->getPaymentMethod()
            ->willReturn(BoostPaymentMethod::CASH);
        $boost->getPaymentAmount()
            ->willReturn(1.00);
        $boost->getPaymentTxId()
            ->willReturn('');
        $boost->getDailyBid()
            ->willReturn(1.00);
        $boost->getDurationDays()
            ->willReturn(1);
        $boost->getStatus()
            ->willReturn(BoostStatus::PENDING);
        $boost->getCreatedTimestamp()
            ->willReturn(null);
        $boost->getApprovedTimestamp()
            ->willReturn(null);
        $boost->getUpdatedTimestamp()
            ->willReturn(null);

        $this->createBoost($boost)
            ->shouldBeEqualTo(true);
    }

    public function it_should_create_boost_with_timestamps(
        Boost $boost,
        PDOStatement $statement
    ): void {
        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $query = "INSERT INTO boosts (guid, owner_guid, entity_guid, target_suitability, target_location, goal, goal_button_text, goal_button_url, payment_method, payment_amount, payment_tx_id, daily_bid, duration_days, status, created_timestamp, approved_timestamp, updated_timestamp)
                    VALUES (:guid, :owner_guid, :entity_guid, :target_suitability, :target_location, :goal, :goal_button_text, :goal_button_url, :payment_method, :payment_amount, :payment_tx_id, :daily_bid, :duration_days, :status, :created_timestamp, :approved_timestamp, :updated_timestamp)";

        $this->mysqlClientWriter->prepare($query)
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::that(function ($arg) {
            return $arg["created_timestamp"] === "1970-01-01T00:00:01+00:00" &&
                $arg["approved_timestamp"] === "1970-01-01T00:00:01+00:00" &&
                $arg["updated_timestamp"] === "1970-01-01T00:00:01+00:00";
        }))
            ->shouldBeCalledOnce();

        $boost->getGuid()
            ->willReturn('1234');
        $boost->getOwnerGuid()
            ->willReturn('1235');
        $boost->getEntityGuid()
            ->willReturn('1236');
        $boost->getTargetSuitability()
            ->willReturn(BoostTargetSuitability::SAFE);
        $boost->getTargetLocation()
            ->willReturn(BoostTargetLocation::NEWSFEED);
        $boost->getGoal()
            ->willReturn(null);
        $boost->getGoalButtonText()
            ->willReturn(null);
        $boost->getGoalButtonUrl()
            ->willReturn(null);

        $boost->getPaymentMethod()
            ->willReturn(BoostPaymentMethod::CASH);
        $boost->getPaymentAmount()
            ->willReturn(1.00);
        $boost->getPaymentTxId()
            ->willReturn('');
        $boost->getDailyBid()
            ->willReturn(1.00);
        $boost->getDurationDays()
            ->willReturn(1);
        $boost->getStatus()
            ->willReturn(BoostStatus::PENDING);
        $boost->getCreatedTimestamp()
            ->willReturn(1);
        $boost->getApprovedTimestamp()
            ->willReturn(1);
        $boost->getUpdatedTimestamp()
            ->willReturn(1);

        $this->createBoost($boost)
            ->shouldBeEqualTo(true);
    }

    public function it_should_get_boosts(
        PDOStatement $statement
    ): void {
        $boostData = [
            'guid' => '123',
            'owner_guid' => '123',
            'entity_guid' => '123',
            'target_location' => 1,
            'target_suitability' => 1,
            'payment_method' => 1,
            'payment_amount' => 20,
            'daily_bid' => 10,
            'duration_days' => 2,
            'status' => 1,
            'reason' => null,
            'payment_tx_id' => null,
            'created_timestamp' => date('c', time()),
            'total_views' => 100,
            'payment_guid' => null
        ];

        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $statement->rowCount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $statement->fetchAll(Argument::type('integer'))
            ->shouldBeCalledOnce()
            ->willReturn([$boostData]);

        $this->mysqlClientReader->prepare(Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->getBoosts()
            ->shouldYieldAnInstanceOf(Boost::class);
    }

    public function it_should_get_boosts_by_status(
        PDOStatement $statement
    ): void {
        $boostData = [
            'guid' => '123',
            'owner_guid' => '123',
            'entity_guid' => '123',
            'target_location' => 1,
            'target_suitability' => 1,
            'payment_method' => 1,
            'payment_amount' => 20,
            'daily_bid' => 10,
            'duration_days' => 2,
            'status' => 1,
            'reason' => null,
            'payment_tx_id' => null,
            'created_timestamp' => date('c', time()),
            'total_views' => 150,
            'payment_guid' => null
        ];

        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $statement->rowCount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $statement->fetchAll(Argument::type('integer'))
            ->shouldBeCalledOnce()
            ->willReturn([$boostData]);

        $this->mysqlClientReader->prepare(Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->getBoosts(
            targetStatus: BoostStatus::PENDING
        )
            ->shouldYieldAnInstanceOf(Boost::class);
    }

    public function it_should_get_boosts_by_status_and_ranking_for_safe_queue(
        PDOStatement $statement
    ): void {
        $boostData = [
            'guid' => '123',
            'owner_guid' => '123',
            'entity_guid' => '123',
            'target_location' => 1,
            'target_suitability' => 1,
            'payment_method' => 1,
            'payment_amount' => 20,
            'daily_bid' => 10,
            'duration_days' => 2,
            'status' => 1,
            'reason' => null,
            'payment_tx_id' => null,
            'created_timestamp' => date('c', time()),
            'total_views' => 175,
            'payment_guid' => null
        ];

        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $statement->rowCount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $statement->fetchAll(Argument::type('integer'))
            ->shouldBeCalledOnce()
            ->willReturn([$boostData]);

        $this->mysqlClientReader->prepare(Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->getBoosts(
            targetStatus: BoostStatus::PENDING,
            orderByRanking: true
        )
            ->shouldYieldAnInstanceOf(Boost::class);
    }

    public function it_should_get_boosts_by_status_and_ranking_for_controversial_queue(
        PDOStatement $statement
    ): void {
        $boostData = [
            'guid' => '123',
            'owner_guid' => '123',
            'entity_guid' => '123',
            'target_location' => 1,
            'target_suitability' => 1,
            'payment_method' => 1,
            'payment_amount' => 20,
            'daily_bid' => 10,
            'duration_days' => 2,
            'status' => 1,
            'reason' => null,
            'payment_tx_id' => null,
            'created_timestamp' => date('c', time()),
            'total_views' => 200,
            'payment_guid' => null
        ];

        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $statement->rowCount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $statement->fetchAll(Argument::type('integer'))
            ->shouldBeCalledOnce()
            ->willReturn([$boostData]);

        $this->mysqlClientReader->prepare(Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->getBoosts(
            targetStatus: BoostStatus::PENDING,
            orderByRanking: true,
            targetAudience: BoostTargetAudiences::CONTROVERSIAL
        )
            ->shouldYieldAnInstanceOf(Boost::class);
    }

    public function it_should_get_boost_by_guid(
        PDOStatement $statement
    ): void {
        $boostData = [
            'guid' => '123',
            'owner_guid' => '123',
            'entity_guid' => '123',
            'target_location' => 1,
            'target_suitability' => 1,
            'payment_method' => 1,
            'payment_amount' => 20,
            'daily_bid' => 10,
            'duration_days' => 2,
            'status' => 1,
            'reason' => null,
            'payment_tx_id' => null,
            'created_timestamp' => date('c', time()),
            'total_views' => 225
        ];
        $query = "SELECT boosts.*,summary.total_views FROM boosts  LEFT JOIN (
            SELECT guid, SUM(views) as total_views FROM boost_summaries
            GROUP BY 1
        ) summary
        ON boosts.guid=summary.guid WHERE boosts.guid = :guid";

        $statement->execute()
            ->shouldBeCalledOnce();

        $statement->rowCount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $statement->fetch(Argument::type('integer'))
            ->shouldBeCalledOnce()
            ->willReturn($boostData);

        $this->mysqlClientReader->prepare(Argument::that(function ($arg) {
            return $arg === "SELECT boosts.*,summary.total_views FROM boosts LEFT JOIN (
                SELECT guid, SUM(views) as total_views FROM boost_summaries
                GROUP BY 1
            ) summary
            ON boosts.guid=summary.guid WHERE boosts.guid = :guid";
        }))
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->getBoostByGuid('123')
            ->shouldReturnAnInstanceOf(Boost::class);
    }

    public function it_should_approve_boost(
        PDOStatement $statement
    ): void {
        $adminGuid = '234';

        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $query = "UPDATE boosts SET status = :status, approved_timestamp = :approved_timestamp, updated_timestamp = :updated_timestamp, admin_guid = :admin_guid WHERE guid = :guid";
        $this->mysqlClientWriter->prepare($query)
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->approveBoost('123', $adminGuid)
            ->shouldBeEqualTo(true);
    }

    public function it_should_reject_boost(
        PDOStatement $statement
    ): void {
        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $query = "UPDATE boosts SET status = :status, updated_timestamp = :updated_timestamp, reason = :reason WHERE guid = :guid";
        $this->mysqlClientWriter->prepare($query)
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->rejectBoost('123', BoostRejectionReason::WRONG_AUDIENCE)
            ->shouldBeEqualTo(true);
    }

    public function it_should_update_boost_status(
        PDOStatement $statement,
        UpdateQuery $updateQuery
    ): void {
        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->mysqlClientWriter->prepare(Argument::type('string'))
            ->willReturn($statement);

        $updateQuery->table('boosts')
            ->shouldBeCalledOnce()
            ->willReturn($updateQuery);

        $updateQuery->set(Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn($updateQuery);

        $updateQuery->where('guid', Operator::EQ, Argument::type(RawExp::class))
            ->shouldBeCalledOnce()
            ->willReturn($updateQuery);

        $updateQuery->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlClientWriterHandler->update()
            ->shouldBeCalledOnce()
            ->willReturn($updateQuery);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->updateStatus('123', BoostStatus::FAILED)
            ->shouldBeEqualTo(true);
    }

    public function it_should_force_reject_by_entity_guid(
        PDOStatement $statement
    ): void {
        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->mysqlClientWriter->prepare(Argument::that(function ($arg) {
            return $arg === "UPDATE boosts
            SET status = :status,
                updated_timestamp = :updated_timestamp,
                reason = :reason
            WHERE entity_guid = :entity_guid AND (status = 2 OR status = 1)";
        }))
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::that(function ($arg) {
            return $arg['status'] = BoostStatus::REJECTED &&
                is_string($arg['updated_timestamp']) &&
                $arg['reason'] === BoostRejectionReason::REPORT_UPHELD &&
                $arg['entity_guid'] === '123';
        }))
            ->shouldBeCalledOnce();

        $this->forceRejectByEntityGuid('123', BoostRejectionReason::REPORT_UPHELD)
            ->shouldBeEqualTo(true);
    }

    public function it_should_get_admin_stats(PDOStatement $statement)
    {
        $expectedResponse = [
            'safe_count' => 24,
            'controversial_count' => 82
        ];

        $query = "SELECT
            SUM(CASE WHEN boosts.target_suitability = :safe_audience THEN 1 ELSE 0 END) as safe_count,
            SUM(CASE WHEN boosts.target_suitability = :controversial_audience THEN 1 ELSE 0 END) AS controversial_count
            FROM boosts WHERE (status = :status)";

        $this->mysqlClientReader->prepare(Argument::that(function ($prepared) use ($query) {
            return $prepared === $query;
        }))
            ->shouldBeCalled()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::that(function ($values) {
            return $values['status'] === 1 &&
                $values['safe_audience'] === 1 &&
                $values['controversial_audience'] === 2;
        }))
            ->shouldBeCalled();

        $statement->execute()
            ->shouldBeCalled();

        $statement->fetch(PDO::FETCH_ASSOC)
            ->shouldBeCalled()
            ->willReturn($expectedResponse);

        $this->getAdminStats()->shouldBe($expectedResponse);
    }

    public function it_should_get_boost_status_counts(PDOStatement $statement)
    {
        $formattedReturnValue = [
            BoostStatus::APPROVED => 22,
            BoostStatus::CANCELLED => 2,
            BoostStatus::REJECTED => 5
        ];

        $expectedResponse = [
            ['status' => BoostStatus::APPROVED, 'statusCount' => 22],
            ['status' => BoostStatus::CANCELLED, 'statusCount' => 2],
            ['status' => BoostStatus::REJECTED, 'statusCount' => 5]
        ];

        $statuses = [
            BoostStatus::APPROVED,
            BoostStatus::CANCELLED,
            BoostStatus::REJECTED
        ];

        $ownerGuid = '234';
        $limit = 30;

        $query = "SELECT status, count(status) AS statusCount
            FROM
                (
                    SELECT status
                    FROM
                        boosts
                    WHERE owner_guid = :owner_guid AND (status = 2 OR status = 10 OR status = 3)
                    ORDER BY
                        updated_timestamp DESC
                    LIMIT :limit
                ) as boostsStatuses
            GROUP BY status";

        $this->mysqlClientReader->prepare(Argument::that(function ($prepared) use ($query) {
            return $prepared === $query;
        }))
            ->shouldBeCalled()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::that(function ($values) use ($ownerGuid, $limit) {
            return $values['owner_guid'] === $ownerGuid &&
                $values['limit'] === $limit;
        }))
            ->shouldBeCalled();

        $statement->execute()
            ->shouldBeCalled();

        $statement->fetchAll(PDO::FETCH_ASSOC)
            ->shouldBeCalled()
            ->willReturn($expectedResponse);

        $this->getBoostStatusCounts(
            $ownerGuid,
            $statuses,
            $limit
        )->shouldBe($formattedReturnValue);
    }
}
