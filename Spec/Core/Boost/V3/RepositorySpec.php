<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Boost\V3;

use Minds\Core\Boost\V3\Enums\BoostStatus;
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

class RepositorySpec extends ObjectBehavior
{
    private Collaborator $mysqlHandler;
    private Collaborator $mysqlClientReader;
    private Collaborator $mysqlClientWriter;
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
        EntitiesBuilder $entitiesBuilder
    ): void {
        $this->mysqlHandler = $mysqlHandler;

        $this->mysqlClientReader = $mysqlClientReader;
        $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_REPLICA)
            ->willReturn($this->mysqlClientReader);

        $this->mysqlClientWriter = $mysqlClientWriter;
        $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_MASTER)
            ->willReturn($this->mysqlClientWriter);

        $this->entitiesBuilder = $entitiesBuilder;

        $this->beConstructedWith($this->mysqlHandler, $this->entitiesBuilder);
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

        $query = "INSERT INTO boosts (guid, owner_guid, entity_guid, target_suitability, target_location, payment_method, payment_amount, payment_tx_id, daily_bid, duration_days, status)
                    VALUES (:guid, :owner_guid, :entity_guid, :target_suitability, :target_location, :payment_method, :payment_amount, :payment_tx_id, :daily_bid, :duration_days, :status)";
        $this->mysqlClientWriter->prepare($query)
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->createBoost($boost)
            ->shouldBeEqualTo(true);
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
            'payment_tx_id' => null,
            'created_timestamp' => date('c', time())
        ];
        $query = "SELECT * FROM boosts WHERE guid = :guid";

        $statement->execute()
            ->shouldBeCalledOnce();

        $statement->rowCount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $statement->fetch(Argument::type('integer'))
            ->shouldBeCalledOnce()
            ->willReturn($boostData);

        $this->mysqlClientReader->prepare($query)
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
        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $query = "UPDATE boosts SET status = :status, approved_timestamp = :approved_timestamp, updated_timestamp = :updated_timestamp WHERE guid = :guid";
        $this->mysqlClientWriter->prepare($query)
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->approveBoost('123')
            ->shouldBeEqualTo(true);
    }

    public function it_should_reject_boost(
        PDOStatement $statement
    ): void {
        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $query = "UPDATE boosts SET status = :status, updated_timestamp = :updated_timestamp WHERE guid = :guid";
        $this->mysqlClientWriter->prepare($query)
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->rejectBoost('123')
            ->shouldBeEqualTo(true);
    }

    public function it_should_update_boost_status(
        PDOStatement $statement
    ): void {
        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $query = "UPDATE boosts SET status = :status, updated_timestamp = :updated_timestamp WHERE guid = :guid";
        $this->mysqlClientWriter->prepare($query)
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->updateStatus('123', BoostStatus::FAILED)
            ->shouldBeEqualTo(true);
    }
}
