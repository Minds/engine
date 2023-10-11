<?php

namespace Spec\Minds\Core\Entities\Repositories;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Repositories\MySQLRepository;
use Minds\Entities\Activity;
use Minds\Entities\Group;
use Minds\Entities\Image;
use Minds\Entities\User;
use Minds\Entities\Video;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class MySQLRepositorySpec extends ObjectBehavior
{
    private Collaborator $mysqlClientMock;
    private Collaborator $mysqlMasterMock;
    private Collaborator $mysqlReplicaMock;

    public function let(
        Client $mysqlClientMock,
        Config $configMock,
        PDO $mysqlMasterMock,
        PDO $mysqlReplicaMock,
    ) {
        $this->beConstructedWith($configMock, $mysqlClientMock, Di::_()->get('Logger'));

        $this->mysqlClientMock = $mysqlClientMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::MASTER)
            ->shouldBeCalled()
            ->willReturn($mysqlMasterMock);
        $this->mysqlMasterMock = $mysqlMasterMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::REPLICA)
            ->shouldBeCalled()
            ->willReturn($mysqlReplicaMock);
        $this->mysqlReplicaMock = $mysqlReplicaMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(MySQLRepository::class);
    }

    public function it_should_load_an_entity_from_a_guid(PDOStatement $pdoStatementMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())
            ->shouldBeCalled()
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute()->shouldBeCalled();

        $pdoStatementMock->fetchAll(PDO::FETCH_NUM)
            ->shouldBeCalled()
            ->willReturn([
                [
                    1, // e.tenant_id
                    123, // e.guid
                    'activity', // e.type
                    // ...
                    'message', // a.message
                ]
            ]);

        $pdoStatementMock->rowCount()->shouldBeCalled()->willReturn(1);

        $pdoStatementMock->getColumnMeta(0)->willReturn([
            'name' => 'tenant_id',
            'table' => 'e',
        ]);

        $pdoStatementMock->getColumnMeta(1)->willReturn([
            'name' => 'guid',
            'table' => 'e',
        ]);

        $pdoStatementMock->getColumnMeta(2)->willReturn([
            'name' => 'type',
            'table' => 'e',
        ]);

        $pdoStatementMock->getColumnMeta(3)->willReturn([
            'name' => 'message',
            'table' => 'a',
        ]);

        $this->mysqlClientMock->bindValuesToPreparedStatement($pdoStatementMock, [ 'guid' => 123 ])
            ->shouldBeCalled();

        $entity = $this->loadFromGuid(123);
        $entity->getGuid()->shouldBe('123');
    }

    public function it_should_not_load_an_entity_from_a_guid_that_does_not_exist(PDOStatement $pdoStatementMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())
            ->shouldBeCalled()
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute()->shouldBeCalled();

        $pdoStatementMock->fetchAll(PDO::FETCH_NUM)
            ->shouldBeCalled()
            ->willReturn([]);

        $pdoStatementMock->rowCount()->shouldBeCalled()->willReturn(0);

        $this->mysqlClientMock->bindValuesToPreparedStatement($pdoStatementMock, [ 'guid' => 123 ])
            ->shouldBeCalled();

        $this->loadFromGuid(123)->shouldBe(null);
    }

    public function it_should_load_a_user_from_their_username(PDOStatement $pdoStatementMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())
            ->shouldBeCalled()
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            'val' => 'minds'
        ])->shouldBeCalled();

        $pdoStatementMock->fetchAll(PDO::FETCH_NUM)
            ->shouldBeCalled()
            ->willReturn([
                [
                    1, // e.tenant_id
                    123, // e.guid
                    'user', // e.type
                    // ...
                    'username', // u.username
                ]
            ]);

        $pdoStatementMock->rowCount()->shouldBeCalled()->willReturn(1);

        $pdoStatementMock->getColumnMeta(0)->willReturn([
            'name' => 'tenant_id',
            'table' => 'e',
        ]);

        $pdoStatementMock->getColumnMeta(1)->willReturn([
            'name' => 'guid',
            'table' => 'e',
        ]);

        $pdoStatementMock->getColumnMeta(2)->willReturn([
            'name' => 'type',
            'table' => 'e',
        ]);

        $pdoStatementMock->getColumnMeta(3)->willReturn([
            'name' => 'username',
            'table' => 'u',
        ]);

        $user = $this->loadFromIndex('username', 'minds');
        $user->getGuid()->shouldBe('123');
    }

    public function it_should_not_load_a_user_from_their_username_if_not_found(PDOStatement $pdoStatementMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())
            ->shouldBeCalled()
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            'val' => 'minds'
        ])->shouldBeCalled();

        $pdoStatementMock->fetchAll(PDO::FETCH_NUM)
            ->shouldBeCalled()
            ->willReturn([
            ]);

        $pdoStatementMock->rowCount()->shouldBeCalled()->willReturn(0);


        $this->loadFromIndex('username', 'minds')->shouldBe(null);
    }

    // Create

    public function it_should_create_an_activity(PDOStatement $pdoStatementMock)
    {
        $activity = new Activity();
        $activity->guid = 123;
        $activity->owner_guid = 456;
        $activity->message = 'hello tests';

        $this->mysqlMasterMock->inTransaction()->willReturn(false);#
        $this->mysqlMasterMock->beginTransaction()->shouldBeCalled();

        $this->mysqlMasterMock->quote(Argument::any())->willReturn("");

        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            'guid' => '123',
            'owner_guid' => '456',
        ])->shouldBeCalled()->willReturn(true);

        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->mysqlClientMock->bindValuesToPreparedStatement(
            $pdoStatementMock,
            Argument::type('array')
        )
            ->shouldBeCalled();

        $this->mysqlMasterMock->commit()->shouldBeCalled();

        $this->create($activity)->shouldBe(true);
    }

    public function it_should_create_an_image(PDOStatement $pdoStatementMock)
    {
        $image = new Image();
        $image->guid = 123;
        $image->owner_guid = 456;

        $this->mysqlMasterMock->inTransaction()->willReturn(false);#
        $this->mysqlMasterMock->beginTransaction()->shouldBeCalled();

        $this->mysqlMasterMock->quote(Argument::any())->willReturn("");

        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            'guid' => '123',
            'owner_guid' => '456',
        ])->shouldBeCalled()->willReturn(true);

        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->mysqlClientMock->bindValuesToPreparedStatement(
            $pdoStatementMock,
            Argument::type('array')
        )
            ->shouldBeCalled();

        $this->mysqlMasterMock->commit()->shouldBeCalled();

        $this->create($image)->shouldBe(true);
    }

    public function it_should_create_a_video(PDOStatement $pdoStatementMock)
    {
        $video = new Video();
        $video->guid = 123;
        $video->owner_guid = 456;

        $this->mysqlMasterMock->inTransaction()->willReturn(false);#
        $this->mysqlMasterMock->beginTransaction()->shouldBeCalled();

        $this->mysqlMasterMock->quote(Argument::any())->willReturn("");

        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            'guid' => '123',
            'owner_guid' => '456',
        ])->shouldBeCalled()->willReturn(true);

        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->mysqlClientMock->bindValuesToPreparedStatement(
            $pdoStatementMock,
            Argument::type('array')
        )
            ->shouldBeCalled();

        $this->mysqlMasterMock->commit()->shouldBeCalled();

        $this->create($video)->shouldBe(true);
    }

    public function it_should_create_a_group(PDOStatement $pdoStatementMock)
    {
        $group = new Group();
        $group->setGuid(123);
        $group->setOwner_guid(456);

        $this->mysqlMasterMock->inTransaction()->willReturn(false);#
        $this->mysqlMasterMock->beginTransaction()->shouldBeCalled();

        $this->mysqlMasterMock->quote(Argument::any())->willReturn("");

        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            'guid' => '123',
            'owner_guid' => '456',
        ])->shouldBeCalled()->willReturn(true);

        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->mysqlClientMock->bindValuesToPreparedStatement(
            $pdoStatementMock,
            Argument::type('array')
        )
            ->shouldBeCalled();

        $this->mysqlMasterMock->commit()->shouldBeCalled();

        $this->create($group)->shouldBe(true);
    }

    public function it_should_create_a_user(PDOStatement $pdoStatementMock)
    {
        $user = new User();
        $user->guid = 123;
        $user->owner_guid = 0;

        $this->mysqlMasterMock->inTransaction()->willReturn(false);#
        $this->mysqlMasterMock->beginTransaction()->shouldBeCalled();

        $this->mysqlMasterMock->quote(Argument::any())->willReturn("");

        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            'guid' => '123',
            'owner_guid' => '123',
        ])->shouldBeCalled()->willReturn(true);

        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->mysqlClientMock->bindValuesToPreparedStatement(
            $pdoStatementMock,
            Argument::type('array')
        )
            ->shouldBeCalled();

        $this->mysqlMasterMock->commit()->shouldBeCalled();

        $this->create($user)->shouldBe(true);
    }

    // Update

    public function it_should_update_an_activity(PDOStatement $pdoStatementMock)
    {
        $activity = new Activity();
        $activity->guid = 123;
        $activity->owner_guid = 456;
        $activity->message = 'hello tests';

        $this->mysqlMasterMock->inTransaction()->willReturn(false);#
        $this->mysqlMasterMock->beginTransaction()->shouldBeCalled();

        $this->mysqlMasterMock->quote(Argument::any())->willReturn("");

        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            'guid' => '123',
        ])->shouldBeCalled()->willReturn(true);

        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->mysqlClientMock->bindValuesToPreparedStatement(
            $pdoStatementMock,
            [
                'guid' => '123',
                'message' => 'hello tests',
                'time_updated' => date('c', time()),
            ]
        )
            ->shouldBeCalled();

        $this->mysqlMasterMock->commit()->shouldBeCalled();

        $this->update($activity, ['message', 'access_id'])->shouldBe(true);
    }

    public function it_should_update_an_image(PDOStatement $pdoStatementMock)
    {
        $image = new Image();
        $image->guid = 123;
        $image->owner_guid = 456;

        $this->mysqlMasterMock->inTransaction()->willReturn(false);#
        $this->mysqlMasterMock->beginTransaction()->shouldBeCalled();

        $this->mysqlMasterMock->quote(Argument::any())->willReturn("");

        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            'guid' => '123',
        ])->shouldBeCalled()->willReturn(true);

        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->mysqlClientMock->bindValuesToPreparedStatement(
            $pdoStatementMock,
            [
                'guid' => '123',
                'time_updated' => date('c', time()),
            ]
        )
            ->shouldBeCalled();

        $this->mysqlMasterMock->commit()->shouldBeCalled();

        $this->update($image, ['access_id'])->shouldBe(true);
    }

    public function it_should_update_a_video(PDOStatement $pdoStatementMock)
    {
        $video = new Video();
        $video->guid = 123;
        $video->owner_guid = 456;

        $this->mysqlMasterMock->inTransaction()->willReturn(false);#
        $this->mysqlMasterMock->beginTransaction()->shouldBeCalled();

        $this->mysqlMasterMock->quote(Argument::any())->willReturn("");

        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            'guid' => '123',
        ])->shouldBeCalled()->willReturn(true);

        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->mysqlClientMock->bindValuesToPreparedStatement(
            $pdoStatementMock,
            [
                'guid' => '123',
                'time_updated' => date('c', time()),
            ]
        )
            ->shouldBeCalled();

        $this->mysqlMasterMock->commit()->shouldBeCalled();

        $this->update($video, ['access_id'])->shouldBe(true);
    }

    public function it_should_update_a_group(PDOStatement $pdoStatementMock)
    {
        $group = new Group();
        $group->setGuid(123);

        $this->mysqlMasterMock->inTransaction()->willReturn(false);#
        $this->mysqlMasterMock->beginTransaction()->shouldBeCalled();

        $this->mysqlMasterMock->quote(Argument::any())->willReturn("");

        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->mysqlClientMock->bindValuesToPreparedStatement(
            $pdoStatementMock,
            [
                'guid' => '123',
                'time_updated' => date('c', time()),
            ]
        )
            ->shouldBeCalled();

        $this->mysqlMasterMock->commit()->shouldBeCalled();

        $this->update($group, ['access_id'])->shouldBe(true);
    }

    public function it_should_update_a_user(PDOStatement $pdoStatementMock)
    {
        $user = new User();
        $user->guid = 123;
        $user->owner_guid = 0;
        $user->name = 'Minds';

        $this->mysqlMasterMock->inTransaction()->willReturn(false);#
        $this->mysqlMasterMock->beginTransaction()->shouldBeCalled();

        $this->mysqlMasterMock->quote(Argument::any())->willReturn("");

        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->mysqlClientMock->bindValuesToPreparedStatement(
            $pdoStatementMock,
            [
                'guid' => '123',
                'name' => 'Minds',
                'time_updated' => date('c', time()),
            ]
        )
            ->shouldBeCalled();

        $this->mysqlMasterMock->commit()->shouldBeCalled();

        $this->update($user, ['name', 'access_id'])->shouldBe(true);
    }

    // Delete

    public function it_should_delete_an_activity(PDOStatement $pdoStatementMock)
    {
        $activity = new Activity();
        $activity->guid = 123;
        $activity->owner_guid = 456;

        $this->mysqlMasterMock->inTransaction()->willReturn(false);
        $this->mysqlMasterMock->beginTransaction()->shouldBeCalled();

        $this->mysqlMasterMock->quote(Argument::any())->willReturn("");

        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->mysqlClientMock->bindValuesToPreparedStatement(
            $pdoStatementMock,
            [
                'guid' => '123',
            ]
        )
            ->shouldBeCalled();

        $this->mysqlMasterMock->commit()->shouldBeCalled();

        $this->delete($activity)->shouldBe(true);
    }

    public function it_should_delete_an_image(PDOStatement $pdoStatementMock)
    {
        $image = new Image();
        $image->guid = 123;
        $image->owner_guid = 456;

        $this->mysqlMasterMock->inTransaction()->willReturn(false);
        $this->mysqlMasterMock->beginTransaction()->shouldBeCalled();

        $this->mysqlMasterMock->quote(Argument::any())->willReturn("");

        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->mysqlClientMock->bindValuesToPreparedStatement(
            $pdoStatementMock,
            [
                'guid' => '123',
            ]
        )
            ->shouldBeCalled();

        $this->mysqlMasterMock->commit()->shouldBeCalled();

        $this->delete($image)->shouldBe(true);
    }

    public function it_should_delete_a_video(PDOStatement $pdoStatementMock)
    {
        $video = new Video();
        $video->guid = 123;
        $video->owner_guid = 456;

        $this->mysqlMasterMock->inTransaction()->willReturn(false);
        $this->mysqlMasterMock->beginTransaction()->shouldBeCalled();

        $this->mysqlMasterMock->quote(Argument::any())->willReturn("");

        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->mysqlClientMock->bindValuesToPreparedStatement(
            $pdoStatementMock,
            [
                'guid' => '123',
            ]
        )
            ->shouldBeCalled();

        $this->mysqlMasterMock->commit()->shouldBeCalled();

        $this->delete($video)->shouldBe(true);
    }

    public function it_should_delete_a_group(PDOStatement $pdoStatementMock)
    {
        $group = new Group();
        $group->setGuid(123);

        $this->mysqlMasterMock->inTransaction()->willReturn(false);
        $this->mysqlMasterMock->beginTransaction()->shouldBeCalled();

        $this->mysqlMasterMock->quote(Argument::any())->willReturn("");

        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->mysqlClientMock->bindValuesToPreparedStatement(
            $pdoStatementMock,
            [
                'guid' => '123',
            ]
        )
            ->shouldBeCalled();

        $this->mysqlMasterMock->commit()->shouldBeCalled();

        $this->delete($group)->shouldBe(true);
    }

    public function it_should_delete_a_user(PDOStatement $pdoStatementMock)
    {
        $user = new User();
        $user->guid = 123;

        $this->mysqlMasterMock->inTransaction()->willReturn(false);
        $this->mysqlMasterMock->beginTransaction()->shouldBeCalled();

        $this->mysqlMasterMock->quote(Argument::any())->willReturn("");

        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->mysqlClientMock->bindValuesToPreparedStatement(
            $pdoStatementMock,
            [
                'guid' => '123',
            ]
        )
            ->shouldBeCalled();

        $this->mysqlMasterMock->commit()->shouldBeCalled();

        $this->delete($user)->shouldBe(true);
    }
}
