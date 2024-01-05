<?php

namespace Spec\Minds\Core\Notifications\PostSubscriptions\Repositories;

use Minds\Core\Config\Config;
use Minds\Core\Notifications\PostSubscriptions\Repositories\PostSubscriptionsRepository;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use Minds\Core\Notifications\PostSubscriptions\Enums\PostSubscriptionFrequencyEnum;
use Minds\Core\Notifications\PostSubscriptions\Models\PostSubscription;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class PostSubscriptionsRepositorySpec extends ObjectBehavior
{
    private Collaborator $mysqlClientMock;
    private Collaborator $mysqlMasterMock;
    private Collaborator $mysqlReplicaMock;

    public function let(
        Config $configMock,
        MySQLClient $mysqlClientMock,
        PDO $mysqlMasterMock,
        PDO $mysqlReplicaMock
    ) {
        $this->beConstructedWith($mysqlClientMock, $configMock, Di::_()->get('Logger'));


        $this->mysqlClientMock = $mysqlClientMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::MASTER)
            ->willReturn($mysqlMasterMock);
        $this->mysqlMasterMock = $mysqlMasterMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::REPLICA)
            ->willReturn($mysqlReplicaMock);
        $this->mysqlReplicaMock = $mysqlReplicaMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PostSubscriptionsRepository::class);
    }

    public function it_should_get_subscription(PDOStatement $pdoStatementMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            'tenant_id' => -1,
            'user_guid' => 1,
            'entity_guid' => 2,
        ])->willReturn(true);

        $pdoStatementMock->rowCount()->willReturn(1);

        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)->willReturn([
            [
                'user_guid' => '1',
                'entity_guid' => '2',
                'frequency' => 'ALWAYS',
            ]
        ]);

        $postSubscription = $this->get(1, 2);
        $postSubscription->userGuid->shouldBe(1);
        $postSubscription->entityGuid->shouldBe(2);
        $postSubscription->frequency->shouldBe(PostSubscriptionFrequencyEnum::ALWAYS);
    }

    public function it_should_not_get_subscription(PDOStatement $pdoStatementMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            'tenant_id' => -1,
            'user_guid' => 1,
            'entity_guid' => 2,
        ])->willReturn(true);

        $pdoStatementMock->rowCount()->willReturn(0);

        $this->get(1, 2)->shouldBe(null);
    }

    public function it_should_add_subscription(PDOStatement $pdoStatementMock)
    {
        $postSubscription = new PostSubscription(
            userGuid: 1,
            entityGuid: 2,
            frequency: PostSubscriptionFrequencyEnum::ALWAYS,
        );

        $this->mysqlMasterMock->prepare(Argument::any())
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            'tenant_id' => -1,
            'user_guid' => 1,
            'entity_guid' => 2,
            'frequency' => 'ALWAYS'
        ])->willReturn(true);

        $this->upsert($postSubscription)->shouldBe(true);
    }
}
