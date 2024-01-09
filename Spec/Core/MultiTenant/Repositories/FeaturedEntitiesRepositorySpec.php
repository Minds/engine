<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\Repositories;

use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Enums\FeaturedEntityTypeEnum;
use Minds\Core\MultiTenant\Repositories\FeaturedEntitiesRepository;
use Minds\Core\MultiTenant\Types\FeaturedUser;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use PDOStatement;
use PDO;

class FeaturedEntitiesRepositorySpec extends ObjectBehavior
{
    private $mysqlClientMock;
    private $mysqlMasterMock;
    private $mysqlReplicaMock;

    public function let(MySQLClient $mysqlClient, PDO $mysqlMasterMock, PDO $mysqlReplicaMock)
    {
        $this->beConstructedWith($mysqlClient, Di::_()->get(Config::class), Di::_()->get('Logger'));
        $this->mysqlClientMock = $mysqlClient;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::MASTER)
            ->willReturn($mysqlMasterMock);
        $this->mysqlMasterMock = $mysqlMasterMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::REPLICA)
            ->willReturn($mysqlReplicaMock);
        $this->mysqlReplicaMock = $mysqlReplicaMock;
    }


    public function it_is_initializable()
    {
        $this->shouldHaveType(FeaturedEntitiesRepository::class);
    }

    public function it_can_get_featured_entities(
        PDOStatement $pdoStatementMock
    ) {
        $this->mysqlReplicaMock->quote(Argument::any())->shouldBeCalled();
        $this->mysqlReplicaMock->query(Argument::type('string'))->willReturn($pdoStatementMock);
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($pdoStatementMock);
        
        $pdoStatementMock->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'tenant_id' => '123',
                    'type' => 'user',
                    'entity_guid' => '1234567890',
                    'auto_subscribe' => true,
                    'auto_post_subscription' => true,
                    'recommended' => true,
                    'username' => 'username',
                    'name' => 'name'
                ],
                [
                    'tenant_id' => '123',
                    'type' => 'user',
                    'entity_guid' => '1234567891',
                    'auto_subscribe' => true,
                    'auto_post_subscription' => true,
                    'recommended' => true,
                    'username' => 'username2',
                    'name' => 'name2'
                ]
            ]);

        $this->mysqlClientMock->bindValuesToPreparedStatement($pdoStatementMock, [
            'tenant_id' => 123,
            'type' => 'user'
        ])->shouldBeCalled();

        $result = $this->getFeaturedEntities(
            tenantId: 123,
            type: FeaturedEntityTypeEnum::USER,
            limit: 12,
            loadAfter: 0
        );

        $result->shouldYieldLike(new \ArrayIterator([
            new FeaturedUser(
                tenantId: 123,
                entityGuid: 1234567890,
                autoSubscribe: true,
                recommended: true,
                autoPostSubscription: true,
                username: 'username',
                name: 'name'
            ),
            new FeaturedUser(
                tenantId: 123,
                entityGuid: 1234567891,
                autoSubscribe: true,
                recommended: true,
                autoPostSubscription: true,
                username: 'username2',
                name: 'name2'
            )
        ]));
    }

    public function it_can_upsert_featured_entity(
        PDOStatement $pdoStatementMock,
    ) {
        $featuredEntity = new FeaturedUser(
            tenantId: 123,
            entityGuid: 1234567890,
            autoSubscribe: true,
            autoPostSubscription: true,
            recommended: true,
            username: 'username',
            name: 'name'
        );

        $this->mysqlClientMock->bindValuesToPreparedStatement(
            $pdoStatementMock,
            Argument::that(function ($args) {
                unset($args['updated_timestamp']);
                return $args === [
                    "tenant_id" => 123,
                    "entity_guid" => 1234567890,
                    "auto_subscribe" => true,
                    "recommended" => true,
                    "auto_post_subscription" => true
                ];
            })
        )->shouldBeCalled();

        $this->mysqlMasterMock->prepare(Argument::type('string'))->shouldBeCalled()->willReturn($pdoStatementMock);
        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->upsertFeaturedEntity($featuredEntity)->shouldBe($featuredEntity);
    }

    public function it_can_delete_featured_entity(
        PDOStatement $pdoStatementMock,
    ) {
        $tenantId = 123;
        $entityGuid = 1234567890;

        $this->mysqlClientMock->bindValuesToPreparedStatement(
            $pdoStatementMock,
            Argument::that(function ($args) use ($tenantId, $entityGuid) {
                return $args === [
                    "tenant_id" => $tenantId,
                    "entity_guid" => $entityGuid
                ];
            })
        )->shouldBeCalled();

        $this->mysqlMasterMock->prepare(Argument::type('string'))->shouldBeCalled()->willReturn($pdoStatementMock);
        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->deleteFeaturedEntity($tenantId, $entityGuid)->shouldBe(true);
    }
}
