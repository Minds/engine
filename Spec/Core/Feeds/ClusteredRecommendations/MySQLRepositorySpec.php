<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Feeds\ClusteredRecommendations;

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
use Spec\Minds\Common\Traits\CommonMatchers;

class MySQLRepositorySpec extends ObjectBehavior
{
    use CommonMatchers;

    private Collaborator $mysqlHandler;
    private Collaborator $mysqlClientReader;
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
        Config $mindsConfig
    ): void {
        $this->mysqlHandler = $mysqlHandler;

        $this->mysqlClientReader = $mysqlClientReader;
        $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_REPLICA)
            ->willReturn($this->mysqlClientReader);

        $this->mindsConfig = $mindsConfig;

        $this->beConstructedWith($this->mysqlHandler, $this->mindsConfig);
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(MySQLRepository::class);
    }

    public function it_should_get_list_of_recommendations_for_user(
        User $user,
        PDOStatement $statement
    ): void {
        $user->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

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

        $this->mysqlClientReader->prepare(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->getList(0, 12, [], true, "")
            ->shouldYieldAnInstanceOf(ScoredGuid::class);
    }
}
