<?php

namespace Spec\Minds\Core\Nostr;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Nostr\Repository;
use Minds\Core\Data\MySQL;
use Minds\Core\Nostr\NostrEvent;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    protected $entitiesBuilderMock;
    protected $mysqlClientMock;

    public function let(EntitiesBuilder $entitiesBuilder, MySQL\Client $mysqlClient)
    {
        $this->beConstructedWith($entitiesBuilder, $mysqlClient);
        $this->entitiesBuilderMock = $entitiesBuilder;
        $this->mysqlClientMock = $mysqlClient;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_add_to_whitelist(PDO $pdoMock, PDOStatement $pdoStatementMock)
    {
        $pubKey = '36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b';

        $this->mysqlClientMock->getConnection(MySQL\Client::CONNECTION_MASTER)
            ->willReturn($pdoMock);

        $pdoMock->prepare(Argument::type('string'))
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([$pubKey])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->addToWhitelist($pubKey)
            ->shouldBe(true);
    }

    public function it_should_read_whitelist(PDO $pdoMock, PDOStatement $pdoStatementMock)
    {
        $pubKey = '36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b';
        $pubKeyNotFound = '46cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b';

        $this->mysqlClientMock->getConnection(MySQL\Client::CONNECTION_REPLICA)
            ->willReturn($pdoMock);

        $pdoMock->prepare(Argument::type('string'))
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([$pubKey])
            ->shouldBeCalled();

        $pdoStatementMock->execute([$pubKeyNotFound])
            ->shouldBeCalled();

        $pdoStatementMock->fetchAll()
            ->willReturn(
                [ 'pubkey' => $pubKey ], // First call (has result)
                null, // Second call (not data - ie. not found)
            );

        $this->isOnWhitelist($pubKey)
            ->shouldBe(true);

        $this->isOnWhiteList($pubKeyNotFound)
            ->shouldBe(false);
    }

    public function it_should_add_event(PDO $pdoMock, PDOStatement $pdoStatementMock)
    {
        $nostrEvent = $this->getNostrEventMock();

        $this->mysqlClientMock->getConnection(MySQL\Client::CONNECTION_MASTER)
            ->willReturn($pdoMock);

        $pdoMock->prepare(Argument::type('string'))
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            'af5b356facc3cde02254a60effd7e299cb66efe1f4af8bafc52ec3f5413e8a0c', // id
            '36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b', // pubkey
            '2022-07-19T13:51:31+00:00', // created at
            1, // kind
            null, // tags
            "Hello sandbox", // content
            "f6a68256a42f9fd84948e328300d0ca55160c9517cd57e549381ce9106e89946ee58c468b93e7ed419f2aec4844c1e995987d27119f9988e99ea2da8dfafffec", // sandbox
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->addEvent($nostrEvent)
            ->shouldBe(true);
    }

    public function it_should_add_reply(PDO $pdoMock, PDOStatement $pdoStatementMock)
    {
        $eventId = "8933788dafe23ed6ac5a0d20011fde4769e2096972bb777d728ca62c43fa04d0";
        $tags = [[
            "e",
            "50eaadde6fd5a67b9a35f947355e3f90d6043d888008c4dbdb36c06155cf31ea", // Reply event
            "wss://relay.minds.io", // Recommended relay
            "reply" // Marker
        ]];


        $this->mysqlClientMock->getConnection(MySQL\Client::CONNECTION_MASTER)
        ->willReturn($pdoMock);

        $pdoMock->prepare(Argument::type('string'))
            ->willReturn($pdoStatementMock);


        $pdoStatementMock->execute([
            "8933788dafe23ed6ac5a0d20011fde4769e2096972bb777d728ca62c43fa04d0",
            "50eaadde6fd5a67b9a35f947355e3f90d6043d888008c4dbdb36c06155cf31ea",
            "wss://relay.minds.io",
            "reply"
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->addReply($eventId, $tags)
            ->shouldBe(true);
    }

    public function it_should_add_mention(PDO $pdoMock, PDOStatement $pdoStatementMock)
    {
        $eventId = "8933788dafe23ed6ac5a0d20011fde4769e2096972bb777d728ca62c43fa04d0";
        $tags = [[
            "p",
            "c59bb3bb07b087ef9fbd82c9530cf7de9d28adfdeb5076a0ac39fa44b88a49ad"
        ]];


        $this->mysqlClientMock->getConnection(MySQL\Client::CONNECTION_MASTER)
        ->willReturn($pdoMock);

        $pdoMock->prepare(Argument::type('string'))
            ->willReturn($pdoStatementMock);


        $pdoStatementMock->execute([
            "8933788dafe23ed6ac5a0d20011fde4769e2096972bb777d728ca62c43fa04d0",
            "c59bb3bb07b087ef9fbd82c9530cf7de9d28adfdeb5076a0ac39fa44b88a49ad"
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->addMention($eventId, $tags)
            ->shouldBe(true);
    }

    public function it_should_query_events(PDO $pdoMock, PDOStatement $pdoStatementMock)
    {
        $filters = [
            'ids' => [ 'af5b356facc3cde02254a60effd7e299cb66efe1f4af8bafc52ec3f5413e8a0c', 'bf5b356facc3cde02254a60effd7e299cb66efe1f4af8bafc52ec3f5413e8a0c' ],
            'authors' => [ '36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b', '46cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b' ],
            'kinds' => [0,1],
        ];

        $this->mysqlClientMock->getConnection(MySQL\Client::CONNECTION_REPLICA)
            ->willReturn($pdoMock);

        $pdoMock->prepare(Argument::type('string'))
            ->willReturn($pdoStatementMock);

        // Assert correct values are binded
        $pdoStatementMock->bindValue(1, "af5b356facc3cde02254a60effd7e299cb66efe1f4af8bafc52ec3f5413e8a0c", PDO::PARAM_STR)->shouldBeCalled();
        $pdoStatementMock->bindValue(2, "bf5b356facc3cde02254a60effd7e299cb66efe1f4af8bafc52ec3f5413e8a0c", PDO::PARAM_STR)->shouldBeCalled();
        $pdoStatementMock->bindValue(3, "36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b", PDO::PARAM_STR)->shouldBeCalled();
        $pdoStatementMock->bindValue(4, "46cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b", PDO::PARAM_STR)->shouldBeCalled();
        $pdoStatementMock->bindValue(5, 0, PDO::PARAM_INT)->shouldBeCalled();
        $pdoStatementMock->bindValue(6, 1, PDO::PARAM_INT)->shouldBeCalled();
        $pdoStatementMock->bindValue(7, 12, PDO::PARAM_INT)->shouldBeCalled();

        $pdoStatementMock->execute()->shouldBeCalled();

        $pdoStatementMock->fetchAll()
            ->willReturn(
                [
                    [
                        'id' => 'af5b356facc3cde02254a60effd7e299cb66efe1f4af8bafc52ec3f5413e8a0c',
                        'pubkey' => '36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b',
                        'kind' => 1,
                        'created_at' => '2022-07-19T13:51:31+00:00',
                        'content' => 'Hello sandbox',
                        'sig' => 'f6a68256a42f9fd84948e328300d0ca55160c9517cd57e549381ce9106e89946ee58c468b93e7ed419f2aec4844c1e995987d27119f9988e99ea2da8dfafffec',
                    ]
                ],
            );

        $this->getEvents($filters)
            ->shouldYieldLike(new \ArrayIterator([
                $this->getNostrEventMock()

            ]));
    }

    public function it_should_get_imported_activity_guid_for_event(PDO $pdoMock, PDOStatement $pdoStatementMock)
    {
        $this->mysqlClientMock->getConnection(MySQL\Client::CONNECTION_REPLICA)
            ->willReturn($pdoMock);

        $pdoMock->prepare(Argument::type('string'))
            ->willReturn($pdoStatementMock);

        // Assert correct values are binded
        $pdoStatementMock->bindValue(1, "af5b356facc3cde02254a60effd7e299cb66efe1f4af8bafc52ec3f5413e8a0c", PDO::PARAM_STR)->shouldBeCalled();
        $pdoStatementMock->bindValue(2, 1, PDO::PARAM_INT)->shouldBeCalled();
        $pdoStatementMock->bindValue(3, 12, PDO::PARAM_INT)->shouldBeCalled();

        $pdoStatementMock->execute()->shouldBeCalled();

        $pdoStatementMock->fetchAll()
            ->willReturn(
                [
                    [
                        'id' => 'af5b356facc3cde02254a60effd7e299cb66efe1f4af8bafc52ec3f5413e8a0c',
                        'pubkey' => '36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b',
                        'kind' => 1,
                        'created_at' => '2022-07-19T13:51:31+00:00',
                        'content' => 'Hello sandbox',
                        'sig' => 'f6a68256a42f9fd84948e328300d0ca55160c9517cd57e549381ce9106e89946ee58c468b93e7ed419f2aec4844c1e995987d27119f9988e99ea2da8dfafffec',
                        'activity_guid' => '123'
                    ]
                ],
            );

        //
        $activity = new Activity();
        $activity->guid = '123';

        $this->entitiesBuilderMock->single('123')
            ->willReturn($activity);

        //

        $this->getActivityFromNostrId('af5b356facc3cde02254a60effd7e299cb66efe1f4af8bafc52ec3f5413e8a0c')
            ->getGuid()
                ->shouldBe('123');
    }

    public function it_should_insert_activity_guid_to_event(PDO $pdoMock, PDOStatement $pdoStatementMock, Activity $activity)
    {
        $this->mysqlClientMock->getConnection(MySQL\Client::CONNECTION_MASTER)
            ->willReturn($pdoMock);

        $pdoMock->prepare(Argument::type('string'))
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->bindParam(1, 'af5b356facc3cde02254a60effd7e299cb66efe1f4af8bafc52ec3f5413e8a0c', PDO::PARAM_STR)
            ->shouldBeCalled(); // id
        $pdoStatementMock->bindParam(2, '123', PDO::PARAM_STR)
            ->shouldBeCalled(); // guid
        $pdoStatementMock->bindParam(3, '456', PDO::PARAM_STR)
            ->shouldBeCalled(); // owner_guid
        $pdoStatementMock->bindParam(4, true, PDO::PARAM_BOOL)
            ->shouldBeCalled(); // is_external

        $pdoStatementMock->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        //
        $activity->getGuid()
            ->willReturn('123');
        $activity->getOwnerGuid()
            ->willReturn('456');
        $activity->getSource()
            ->willReturn('nostr');
        //

        $this->addActivityToNostrId($activity, 'af5b356facc3cde02254a60effd7e299cb66efe1f4af8bafc52ec3f5413e8a0c')
            ->shouldBe(true);
    }

    public function it_should_add_user_link(PDO $pdoMock, PDOStatement $pdoStatementMock, User $user)
    {
        $this->mysqlClientMock->getConnection(MySQL\Client::CONNECTION_MASTER)
            ->willReturn($pdoMock);

        $pdoMock->prepare(Argument::type('string'))
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->bindParam(1, '36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b', PDO::PARAM_STR)
            ->shouldBeCalled(); // pubkey

        $pdoStatementMock->bindParam(2, '123', PDO::PARAM_STR)
            ->shouldBeCalled(); // guid

        $pdoStatementMock->bindParam(3, true, PDO::PARAM_BOOL)
            ->shouldBeCalled(); // is_external

        $pdoStatementMock->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        //
        $user->getGuid()
            ->willReturn('123');
        $user->getSource()
            ->willReturn('nostr');
        //

        $this->addNostrUser($user, '36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b')
            ->shouldBe(true);
    }

    public function it_should_return_user_from_publickey(PDO $pdoMock, PDOStatement $pdoStatementMock)
    {
        $this->mysqlClientMock->getConnection(MySQL\Client::CONNECTION_REPLICA)
            ->willReturn($pdoMock);

        $pdoMock->prepare(Argument::type('string'))
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            '36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b',
        ])
            ->shouldBeCalled();

        $pdoStatementMock->fetchAll()
            ->willReturn(
                [
                    [
                        'user_guid' => '123'
                    ]
                ],
            );

        //
        $user = new User();
        $user->guid = '123';

        $this->entitiesBuilderMock->single('123')
            ->willReturn($user);

        //

        $this->getUserFromNostrPublicKey('36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b')
            ->getGuid()
                ->shouldBe('123');
    }

    public function it_should_return_user_from_multiple_publickeys(PDO $pdoMock, PDOStatement $pdoStatementMock)
    {
        $this->mysqlClientMock->getConnection(MySQL\Client::CONNECTION_REPLICA)
            ->willReturn($pdoMock);

        $pdoMock->prepare(Argument::type('string'))
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            '36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b',
            '46cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b',
        ])
            ->shouldBeCalled();

        $pdoStatementMock->fetchAll()
            ->willReturn(
                [
                    [
                        'user_guid' => '123'
                    ],
                    [
                        'user_guid' => '456'
                    ]
                ],
            );

        //
        $user = new User();
        $user->guid = '123';

        $this->entitiesBuilderMock->single('123')
            ->willReturn($user);

        $user2 = new User();
        $user2->guid = '456';

        $this->entitiesBuilderMock->single('456')
            ->willReturn($user2);

        //

        $users = $this->getUserFromNostrPublicKeys(['36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b', '46cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b']);
        $users[0]->getGuid()
            ->shouldBe('123');
        $users[1]->getGuid()
            ->shouldBe('456');
    }

    protected function getNostrEventMock(): NostrEvent
    {
        $rawNostrEvent = <<<END
{
    "id": "af5b356facc3cde02254a60effd7e299cb66efe1f4af8bafc52ec3f5413e8a0c",
    "pubkey": "36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b",
    "created_at": 1658238691,
    "kind": 1,
    "tags": [],
    "content": "Hello sandbox",
    "sig": "f6a68256a42f9fd84948e328300d0ca55160c9517cd57e549381ce9106e89946ee58c468b93e7ed419f2aec4844c1e995987d27119f9988e99ea2da8dfafffec"
}
END;

        $rawNostrEventArray = json_decode($rawNostrEvent, true);
        return NostrEvent::buildFromArray($rawNostrEventArray);
    }
}
