<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Feeds\ClusteredRecommendations;

use Closure;
use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Feeds\ClusteredRecommendations\MySQLRepository;
use Minds\Core\Feeds\Elastic\ScoredGuid;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Selective\Database\Connection;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;
use Spec\Minds\Common\Traits\CommonMatchers;

class MySQLRepositorySpec extends ObjectBehavior
{
    use CommonMatchers;

    private Collaborator $mysqlHandler;
    private Collaborator $mysqlClientReader;
    private Collaborator $mysqlClientReaderHandler;
    private Collaborator $mindsConfig;

    /**
     * @param MySQLClient $mysqlHandler
     * @param PDO $mysqlClientReader
     * @param Config $mindsConfig
     * @return void
     * @throws ServerErrorException
     */
    public function let(
        MySQLClient $mysqlHandler,
        PDO    $mysqlClientReader,
        Config $mindsConfig,
        Connection $mysqlClientReaderHandler
    ): void {
        $this->mysqlHandler = $mysqlHandler;

        $this->mysqlClientReader = $mysqlClientReader;
        $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_REPLICA)
            ->willReturn($this->mysqlClientReader);

        $mysqlClientReaderHandler->getPdo()->willReturn($this->mysqlClientReader);
        $this->mysqlClientReaderHandler = $mysqlClientReaderHandler;

        $this->mindsConfig = $mindsConfig;

        $this->beConstructedWith($this->mysqlHandler, $this->mindsConfig, $this->mysqlClientReaderHandler);
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(MySQLRepository::class);
    }

    public function it_should_get_list_of_recommendations_for_user(
        User $user,
        SelectQuery $selectQuery,
        PDOStatement $statement
    ): void {
        $this->setUser($user);

        $statement->fetchAll(PDO::FETCH_ASSOC)
            ->shouldBeCalledOnce()
            ->willReturn([
                [
                    'activity_guid' => 123,
                    'channel_guid' => 124,
                    'adjusted_score' => 1
                ]
            ]);

        $statement->execute()
            ->shouldBeCalledOnce();

        $selectQuery->columns(Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQuery);

        $selectQuery->from(Argument::type(Closure::class))
            ->shouldBeCalledOnce()
            ->willReturn($selectQuery);

        $selectQuery->joinRaw(Argument::type(RawExp::class), Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQuery);

        $selectQuery->leftJoinRaw(Argument::type('string'), Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQuery);

        $selectQuery->orderBy(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQuery);

        $selectQuery->limit(Argument::type('integer'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQuery);

        $selectQuery->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlClientReaderHandler->select()
            ->shouldBeCalledOnce()
            ->willReturn($selectQuery);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->getList(0, 12, [], true, "")
            ->shouldYieldAnInstanceOf(ScoredGuid::class);
    }
}
