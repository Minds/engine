<?php

namespace Spec\Minds\Core\Subscriptions\Relational;

use ArrayIterator;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Guid;
use Minds\Core\Subscriptions\Relational\Repository;
use Minds\Core\Subscriptions\Subscription;
use Minds\Entities\User;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    private $mysqlClientMock;
    private $entitiesBuilderMock;
    private $pdoMock;

    public function let(Client $client, EntitiesBuilder $entitiesBuilder, PDO $pdoMock)
    {
        $this->beConstructedWith($client, $entitiesBuilder);
        $this->mysqlClientMock = $client;
        $this->entitiesBuilderMock = $entitiesBuilder;
        $this->pdoMock = $pdoMock;

        $client->getConnection(Argument::any())
            ->willReturn($pdoMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_add(Subscription $subscription, PDOStatement $stmtMock)
    {
        $subscription->getSubscriberGuid()
            ->willReturn('123');
        $subscription->getPublisherGuid()
            ->willReturn('456');

        $this->pdoMock->prepare(Argument::any())
            ->willReturn($stmtMock);

        $stmtMock->execute([
            'user_guid' => '123',
            'friend_guid' => '456',
            'tenant_id' => null,
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->add($subscription)
            ->shouldBe(true);
    }

    public function it_should_delete(Subscription $subscription, PDOStatement $stmtMock)
    {
        $subscription->getSubscriberGuid()
            ->willReturn('123');
        $subscription->getPublisherGuid()
            ->willReturn('456');

        $this->pdoMock->prepare(Argument::any())
            ->willReturn($stmtMock);

        $stmtMock->execute([
            'user_guid' => '123',
            'friend_guid' => '456',
            //'tenant_id' => null,
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->delete($subscription)
            ->shouldBe(true);
    }

    public function it_should_get_subscriptions_of_subscriptions(
        PDOStatement $stmtMock,
        User $user1Mock,
    ) {
        $this->pdoMock->prepare(Argument::any())
            ->willReturn($stmtMock);

        $stmtMock->execute([
            'user_guid' => '123'
        ])
            ->shouldBeCalled();

        $stmtMock->getIterator()
            ->willYield([
                [
                    'friend_guid' => '789',
                ]
            ]);

        $this->entitiesBuilderMock->single('789')
            ->willReturn($user1Mock);

        $user1Mock->isEnabled()
            ->willReturn(true);

        $this->getSubscriptionsOfSubscriptions('123', '456')
            ->shouldYield(new ArrayIterator([
                $user1Mock->getWrappedObject()
            ]));
    }

    public function it_should_get_count_of_users_i_subscribe_to_that_subscribe_to_x(PDOStatement $stmtMock)
    {
        $this->pdoMock->prepare(Argument::any())
            ->willReturn($stmtMock);

        $stmtMock->execute([
            'user_guid' => '123',
            'friend_guid' => '456'
        ])
            ->shouldBeCalled();

        $stmtMock->fetchAll()
            ->willReturn([
                [
                    'c' => 10
                ]
            ]);

        $this->getSubscriptionsThatSubscribeToCount('123', '456')
            ->shouldBe(10);
    }

    public function it_should_get_list_of_users_i_subscribe_to_that_subscribe_to_x(
        PDOStatement $stmtMock,
        User $user1Mock,
    ) {
        $this->pdoMock->prepare(Argument::any())
            ->willReturn($stmtMock);

        $stmtMock->execute([
            'user_guid' => '123',
            'friend_guid' => '456'
        ])
            ->shouldBeCalled();

        $stmtMock->getIterator()
            ->willYield([
                [
                    'friend_guid' => '789',
                ]
            ]);

        $this->entitiesBuilderMock->single('789')
            ->willReturn($user1Mock);

        $user1Mock->isEnabled()
            ->willReturn(true);

        $this->getSubscriptionsThatSubscribeTo('123', '456')
            ->shouldYield(new ArrayIterator([
                $user1Mock->getWrappedObject()
            ]));
    }

    public function it_should_get_subscribers(
        PDOStatement $stmtMock,
        User $user1Mock
    ) {
        $limit = 10;
        $loadBefore = 20;
        $user1Guid = Guid::build();
        $user1DateTime = date('c', time());

        $this->pdoMock->prepare(Argument::any())
            ->willReturn($stmtMock);

        $this->mysqlClientMock->bindValuesToPreparedStatement(Argument::any(), Argument::any())
            ->shouldBeCalled();

        $stmtMock->execute()
            ->shouldBeCalled();

        $stmtMock->fetchAll(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'user_guid' => $user1Guid,
                    'timestamp' => $user1DateTime
                ]
            ]);

        $this->entitiesBuilderMock->single($user1Guid)
            ->willReturn($user1Mock);

        $this->getSubscribers($user1Guid, $limit, $loadBefore)
            ->shouldYield(new ArrayIterator([
                $user1Mock->getWrappedObject()
            ]));
    }
}
