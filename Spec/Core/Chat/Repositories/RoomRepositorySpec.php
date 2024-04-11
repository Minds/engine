<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Chat\Repositories;

use DateTimeImmutable;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Enums\ChatRoomMemberStatusEnum;
use Minds\Core\Chat\Enums\ChatRoomNotificationStatusEnum;
use Minds\Core\Chat\Enums\ChatRoomRoleEnum;
use Minds\Core\Chat\Enums\ChatRoomTypeEnum;
use Minds\Core\Chat\Repositories\RoomRepository;
use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Log\Logger;
use Minds\Entities\User;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Selective\Database\Connection;
use Selective\Database\DeleteQuery;
use Selective\Database\InsertQuery;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;
use Selective\Database\UpdateQuery;
use Spec\Minds\Common\Traits\CommonMatchers;

class RoomRepositorySpec extends ObjectBehavior
{
    use CommonMatchers;
    private Collaborator $mysqlHandlerMock;
    private Collaborator $mysqlClientWriterHandlerMock;
    private Collaborator $mysqlClientReaderHandlerMock;
    private Collaborator $loggerMock;
    private Collaborator $configMock;

    public function let(
        MySQLClient $mysqlClient,
        Logger      $logger,
        Config      $config,
        Connection  $mysqlMasterConnectionHandler,
        Connection  $mysqlReaderConnectionHandler,
        PDO         $mysqlMasterConnection,
        PDO         $mysqlReaderConnection,
    ): void {
        $this->mysqlHandlerMock = $mysqlClient;

        $this->mysqlHandlerMock->getConnection(MySQLClient::CONNECTION_MASTER)
            ->willReturn($mysqlMasterConnection);
        $mysqlMasterConnectionHandler->getPdo()->willReturn($mysqlMasterConnection);
        $this->mysqlClientWriterHandlerMock = $mysqlMasterConnectionHandler;

        $this->mysqlHandlerMock->getConnection(MySQLClient::CONNECTION_REPLICA)
            ->willReturn($mysqlReaderConnection);
        $mysqlReaderConnectionHandler->getPdo()->willReturn($mysqlReaderConnection);
        $this->mysqlClientReaderHandlerMock = $mysqlReaderConnectionHandler;

        $this->loggerMock = $logger;
        $this->configMock = $config;

        $this->beConstructedThrough('buildForUnitTests', [
            $this->mysqlHandlerMock->getWrappedObject(),
            $this->configMock->getWrappedObject(),
            $this->loggerMock->getWrappedObject(),
            $this->mysqlClientWriterHandlerMock->getWrappedObject(),
            $this->mysqlClientReaderHandlerMock->getWrappedObject(),
        ]);
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(RoomRepository::class);
    }

    public function it_should_create_room(
        InsertQuery $insertQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $pdoStatementMock->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $insertQueryMock->into(RoomRepository::TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $dateMock = new DateTimeImmutable();

        $insertQueryMock->set(
            [
                'tenant_id' => 1,
                'room_guid' => 123,
                'room_type' => ChatRoomTypeEnum::ONE_TO_ONE->name,
                'created_by_user_guid' => 456,
                'created_timestamp' => $dateMock->format('c'),
                'group_guid' => null
            ]
        )
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientWriterHandlerMock->insert()
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $this->createRoom(
            123,
            ChatRoomTypeEnum::ONE_TO_ONE,
            456,
            $dateMock,
            null
        )
            ->shouldEqual(true);
    }

    public function it_should_add_room_member(
        InsertQuery $insertQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $pdoStatementMock->execute([
            'status' => ChatRoomMemberStatusEnum::ACTIVE->name,
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $insertQueryMock->into(RoomRepository::MEMBERS_TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->set(
            Argument::that(
                fn (array $cols): bool => $cols['tenant_id'] === 1 &&
                $cols['room_guid'] === 123 &&
                $cols['member_guid'] === 456 &&
                $cols['status'] === ChatRoomMemberStatusEnum::ACTIVE->name &&
                $cols['role_id'] === ChatRoomRoleEnum::OWNER->name &&
                $cols['joined_timestamp'] !== null
            )
        )
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->onDuplicateKeyUpdate([
            'status' => new RawExp(':status')
        ])
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientWriterHandlerMock->insert()
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $this->addRoomMember(
            123,
            456,
            ChatRoomMemberStatusEnum::ACTIVE,
            ChatRoomRoleEnum::OWNER
        )
            ->shouldEqual(true);
    }

    public function it_should_get_rooms_by_member_NO_OFFSET(
        SelectQuery $selectQueryMock,
        PDOStatement $pdoStatementMock,
        User $userMock
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $userMock->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn(456);

        $this->mysqlHandlerMock->bindValuesToPreparedStatement($pdoStatementMock, [
            'tenant_id' => 1,
            'member_guid' => 456,
            'status' => [ChatRoomMemberStatusEnum::ACTIVE->name],
        ])
            ->shouldBeCalledOnce();

        $pdoStatementMock->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $pdoStatementMock->rowCount()
            ->shouldBeCalledTimes(2)
            ->willReturn(1);

        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->shouldBeCalledOnce()
            ->willReturn([
                [
                    'room_guid' => 123,
                    'room_type' => ChatRoomTypeEnum::ONE_TO_ONE->name,
                    'created_by_user_guid' => 456,
                    'created_timestamp' => '2021-01-01 00:00:00',
                    'group_guid' => null,
                    'last_msg_plain_text' => 'Hello',
                    'last_msg_created_timestamp' => '2021-01-01 00:00:00',
                    'unread_messages_count' => 0,
                ]
            ]);

        $selectQueryMock->columns(
            Argument::that(
                fn (array $cols): bool =>
                    $cols[0] === 'r.*' &&
                    (
                        $cols[1] instanceof RawExp &&
                        $cols[1]->getValue() === "last_msg.plain_text as last_msg_plain_text"
                    ) &&
                    (
                        $cols[2] instanceof RawExp &&
                        $cols[2]->getValue() === "last_msg.created_timestamp as last_msg_created_timestamp"
                    ) &&
                    (
                        $cols[3] instanceof RawExp &&
                        trim($cols[3]->getValue()) === "CASE
                        WHEN
                            COALESCE(rct.message_guid, 0) < last_msg.guid
                        THEN 1
                        ELSE 0
                    END
                    AS unread_messages_count"
                    )
            )
        )
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->from(new RawExp(RoomRepository::TABLE_NAME . " as r"))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->joinRaw(
            new RawExp(RoomRepository::MEMBERS_TABLE_NAME . " as m"),
            'r.room_guid = m.room_guid AND r.tenant_id = m.tenant_id AND m.member_guid = :member_guid',
        )
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->leftJoinRaw(
            Argument::type('callable'),
            "last_msg.room_guid = r.room_guid AND last_msg.tenant_id = r.tenant_id"
        )
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->leftJoinRaw(
            new RawExp(RoomRepository::RECEIPTS_TABLE_NAME . " as rct"),
            'r.room_guid = rct.room_guid AND r.tenant_id = rct.tenant_id AND rct.member_guid = m.member_guid',
        )
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('r.tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->whereWithNamedParameters('m.status', Operator::IN, 'status', 1)
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->orderBy('last_msg.created_timestamp DESC', 'r.created_timestamp DESC')
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->limit(13)
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientReaderHandlerMock->select()
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $this->getRoomsByMember(
            $userMock,
            null,
            12,
            null,
            null
        )
            ->shouldBeArray();
    }

    public function it_should_get_room_guids_by_member(
        SelectQuery $selectQueryMock,
        PDOStatement $pdoStatementMock,
        User $userMock
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $userMock->getGuid()
            ->shouldBeCalledTimes(2)
            ->willReturn(456);

        $pdoStatementMock->execute([
            'tenant_id' => 1,
            'member_guid_1' => 456,
            'member_guid_2' => 456,
            'status_1' => ChatRoomMemberStatusEnum::ACTIVE->name,
            'status_2' => ChatRoomMemberStatusEnum::INVITE_PENDING->name,
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $pdoStatementMock->setFetchMode(PDO::FETCH_COLUMN, 0)
            ->shouldBeCalledOnce();

        $pdoStatementMock->getIterator()
            ->shouldBeCalledOnce()
            ->willYield([
                123
            ]);

        $selectQueryMock->columns([
            'r.room_guid'
        ])
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->from(new RawExp(RoomRepository::TABLE_NAME . " as r"))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->leftJoinRaw(
            new RawExp(RoomRepository::MEMBERS_TABLE_NAME . " as m"),
            'm.room_guid = r.room_guid AND m.tenant_id = r.tenant_id AND m.member_guid = :member_guid_1',
        )
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->leftJoinRaw(
            new RawExp("minds_group_membership as gm"),
            'r.group_guid = gm.group_guid AND gm.user_guid = :member_guid_2',
        )
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('r.tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->whereRaw("(m.status IS NOT NULL AND m.status IN (:status_1, :status_2)) OR
                (gm.group_guid IS NOT NULL AND m.status IS NULL)")
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientReaderHandlerMock->select()
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $this->getRoomGuidsByMember($userMock)
            ->shouldYield([123]);
    }

    public function it_should_get_total_room_members(
        SelectQuery $selectQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $pdoStatementMock->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $pdoStatementMock->fetch(PDO::FETCH_ASSOC)
            ->shouldBeCalledOnce()
            ->willReturn(['total_members' => 1]);

        $selectQueryMock->columns([
            new RawExp('COUNT(member_guid) as total_members')
        ])
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->from(RoomRepository::MEMBERS_TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->whereWithNamedParameters('status', Operator::IN, 'status', 2)
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientReaderHandlerMock->select()
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $this->mysqlHandlerMock->bindValuesToPreparedStatement($pdoStatementMock, [
            'tenant_id' => 1,
            'room_guid' => 123,
            'status' => [ChatRoomMemberStatusEnum::ACTIVE->name, ChatRoomMemberStatusEnum::INVITE_PENDING->name],
        ])
            ->shouldBeCalledOnce();

        $this->getRoomTotalMembers(123)
            ->shouldEqual(1);
    }

    public function it_should_return_true_when_user_is_room_member(
        SelectQuery $selectQueryMock,
        PDOStatement $pdoStatementMock,
        User $userMock
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $userMock->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn(456);

        $pdoStatementMock->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $pdoStatementMock->rowCount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $selectQueryMock->columns([
            'member_guid'
        ])
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->from(RoomRepository::MEMBERS_TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('member_guid', Operator::EQ, new RawExp(':member_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->whereWithNamedParameters('status', Operator::IN, 'status', 1)
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->limit(1)
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientReaderHandlerMock->select()
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $this->mysqlHandlerMock->bindValuesToPreparedStatement($pdoStatementMock, [
            'tenant_id' => 1,
            'room_guid' => 123,
            'member_guid' => 456,
            'status' => [ChatRoomMemberStatusEnum::ACTIVE->name],
        ])
            ->shouldBeCalledOnce();

        $this->isUserMemberOfRoom(
            123,
            $userMock
        )
            ->shouldEqual(true);
    }

    public function it_should_get_user_status_in_room(
        SelectQuery $selectQueryMock,
        PDOStatement $pdoStatementMock,
        User $userMock
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $userMock->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn(456);

        $pdoStatementMock->execute([
            'tenant_id' => 1,
            'room_guid' => 123,
            'member_guid' => 456
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $pdoStatementMock->rowCount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $pdoStatementMock->fetch(PDO::FETCH_ASSOC)
            ->shouldBeCalledOnce()
            ->willReturn(['status' => ChatRoomMemberStatusEnum::ACTIVE->name]);

        $selectQueryMock->columns([
            'status'
        ])
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->from(RoomRepository::MEMBERS_TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('member_guid', Operator::EQ, new RawExp(':member_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->limit(1)
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientReaderHandlerMock->select()
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $this->getUserStatusInRoom(
            $userMock,
            123,
        )
            ->shouldEqual(ChatRoomMemberStatusEnum::ACTIVE);
    }

    // TODO: implement test for getRoomMembers
    public function it_should_get_room_members(
        SelectQuery $selectQueryMock,
        PDOStatement $pdoStatementMock,
        User $userMock
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $userMock->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn(456);

        $pdoStatementMock->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $pdoStatementMock->rowCount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->shouldBeCalledOnce()
            ->willReturn([
                [
                    'member_guid' => 456,
                    'status' => ChatRoomMemberStatusEnum::ACTIVE->name,
                    'role_id' => ChatRoomRoleEnum::OWNER->name,
                    'joined_timestamp' => '2021-01-01 00:00:00',
                    'notifications_status' => ChatRoomNotificationStatusEnum::ALL->name,
                ]
            ]);

        $this->mysqlHandlerMock->bindValuesToPreparedStatement($pdoStatementMock, [
            'tenant_id' => 1,
            'room_guid' => 123,
            'member_guid' => "456",
            'status' => [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ],
        ])
            ->shouldBeCalledOnce();

        $selectQueryMock->columns([
            'm.*',
            'rms.notifications_status'
        ])
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->from(new RawExp(RoomRepository::MEMBERS_TABLE_NAME . ' as m'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->joinRaw(
            new RawExp(RoomRepository::ROOM_MEMBER_SETTINGS_TABLE_NAME . ' as rms'),
            'rms.tenant_id = m.tenant_id AND rms.member_guid = m.member_guid AND rms.room_guid = m.room_guid',
        )
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('m.tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('m.room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->whereWithNamedParameters('m.status', Operator::IN, 'status', 2)
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('m.member_guid', Operator::NOT_EQ, new RawExp(':member_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->orderBy('m.joined_timestamp ASC', 'm.member_guid DESC')
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->limit(13)
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientReaderHandlerMock->select()
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $this->getRoomMembers(
            123,
            $userMock,
            12,
            null,
            true
        )
            ->shouldBeArray();
    }

    // TODO: implement test for getAllRoomMembers

    public function it_should_get_all_room_members(
        SelectQuery $selectQueryMock,
        PDOStatement $pdoStatementMock,
        User $userMock
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $userMock->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn(456);

        $pdoStatementMock->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $pdoStatementMock->setFetchMode(PDO::FETCH_ASSOC)
            ->shouldBeCalledOnce();

        $pdoStatementMock->getIterator()
            ->shouldBeCalledOnce()
            ->willYield([
                [
                    'member_guid' => 456,
                    'status' => ChatRoomMemberStatusEnum::ACTIVE->name,
                    'role_id' => ChatRoomRoleEnum::OWNER->name,
                    'joined_timestamp' => '2021-01-01 00:00:00',
                    'notifications_status' => ChatRoomNotificationStatusEnum::ALL->value,
                ]
            ]);

        $this->mysqlHandlerMock->bindValuesToPreparedStatement($pdoStatementMock, [
            'tenant_id' => 1,
            'room_guid' => 123,
            'member_guid' => "456",
            'status' => [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ],
        ])
            ->shouldBeCalledOnce();

        $selectQueryMock->columns([
            'm.*',
            'rms.notifications_status'
        ])
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->from(new RawExp(RoomRepository::MEMBERS_TABLE_NAME . ' as m'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->joinRaw(
            new RawExp(RoomRepository::ROOM_MEMBER_SETTINGS_TABLE_NAME . ' as rms'),
            'rms.tenant_id = m.tenant_id AND rms.member_guid = m.member_guid AND rms.room_guid = m.room_guid',
        )
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('m.tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('m.room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->whereWithNamedParameters('m.status', Operator::IN, 'status', 2)
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('m.member_guid', Operator::NOT_EQ, new RawExp(':member_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->orderBy('m.joined_timestamp ASC', 'm.member_guid DESC')
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientReaderHandlerMock->select()
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $this->getAllRoomMembers(
            123,
            $userMock,
            true
        )
            ->shouldBeAGeneratorWithValues([
                [
                    'member_guid' => 456,
                    'status' => ChatRoomMemberStatusEnum::ACTIVE->name,
                    'role_id' => ChatRoomRoleEnum::OWNER->name,
                    'joined_timestamp' => '2021-01-01 00:00:00',
                    'notifications_status' => ChatRoomNotificationStatusEnum::ALL->value,
                ]
            ]);
    }

    public function it_should_get_total_room_invite_requests_by_member(
        SelectQuery $selectQueryMock,
        PDOStatement $pdoStatementMock,
        User $userMock
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $userMock->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn(456);

        $pdoStatementMock->execute([
            'tenant_id' => 1,
            'member_guid' => 456,
            'status' => ChatRoomMemberStatusEnum::INVITE_PENDING->name,
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $pdoStatementMock->fetch(PDO::FETCH_ASSOC)
            ->shouldBeCalledOnce()
            ->willReturn(['total_requests' => 1]);

        $selectQueryMock->from(RoomRepository::MEMBERS_TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->columns([
            new RawExp('COUNT(member_guid) as total_requests')
        ])
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('member_guid', Operator::EQ, new RawExp(':member_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('status', Operator::EQ, new RawExp(':status'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientReaderHandlerMock->select()
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $this->getTotalRoomInviteRequestsByMember($userMock)
            ->shouldEqual(1);
    }

    public function it_should_update_room_member_status(
        UpdateQuery $updateQueryMock,
        PDOStatement $pdoStatementMock,
        User $userMock
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $userMock->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn(456);

        $pdoStatementMock->execute(
            Argument::that(
                fn (array $params): bool =>
                    $params['tenant_id'] === 1 &&
                    $params['room_guid'] === 123 &&
                    $params['member_guid'] === "456" &&
                    $params['joined_timestamp'] !== null
            )
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $updateQueryMock->table(RoomRepository::MEMBERS_TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($updateQueryMock);

        $updateQueryMock->set([
            'status' => ChatRoomMemberStatusEnum::ACTIVE->name,
            'joined_timestamp' => new RawExp(':joined_timestamp')
        ])
            ->shouldBeCalledOnce()
            ->willReturn($updateQueryMock);

        $updateQueryMock->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($updateQueryMock);

        $updateQueryMock->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($updateQueryMock);

        $updateQueryMock->where('member_guid', Operator::EQ, new RawExp(':member_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($updateQueryMock);

        $updateQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientWriterHandlerMock->update()
            ->shouldBeCalledOnce()
            ->willReturn($updateQueryMock);

        $this->updateRoomMemberStatus(
            123,
            $userMock,
            ChatRoomMemberStatusEnum::ACTIVE
        )
            ->shouldEqual(true);
    }

    public function it_should_get_one_to_one_room_by_members(
        SelectQuery $selectQueryMock,
        PDOStatement $pdoStatementMock,
        SelectQuery $fistQueryMock,
        PDOStatement $firstPdoStatementMock,
        SelectQuery $secondQueryMock,
        PDOStatement $secondPdoStatementMock
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledTimes(4)->willReturn(1);

        $firstQueryString = $this->getMemberOneToOneRoomsQueryString(0);
        $secondQueryString = $this->getMemberOneToOneRoomsQueryString(1);

        $firstPdoStatementMock->queryString = $firstQueryString;
        $secondPdoStatementMock->queryString = $secondQueryString;

        // Set up mock for subqueries
        $fistQueryMock->columns([
           'r.*'
        ])
            ->shouldBeCalledOnce()
            ->willReturn($fistQueryMock);

        $fistQueryMock->from(new RawExp(RoomRepository::TABLE_NAME . " as r"))
            ->shouldBeCalledOnce()
            ->willReturn($fistQueryMock);

        $fistQueryMock->joinRaw(
            Argument::type('callable'),
            'm.room_guid = r.room_guid',
        )
            ->shouldBeCalledOnce()
            ->willReturn($fistQueryMock);

        $fistQueryMock->where('r.tenant_id', Operator::EQ, new RawExp(":tenant_id_2"))
            ->shouldBeCalledOnce()
            ->willReturn($fistQueryMock);

        $fistQueryMock->where('r.room_type', Operator::EQ, new RawExp(":room_type_1"))
            ->shouldBeCalledOnce()
            ->willReturn($fistQueryMock);

        $fistQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($firstPdoStatementMock);

        $secondQueryMock->columns([
            'r.*'
        ])
            ->shouldBeCalledOnce()
            ->willReturn($secondQueryMock);

        $secondQueryMock->from(new RawExp(RoomRepository::TABLE_NAME . " as r"))
            ->shouldBeCalledOnce()
            ->willReturn($secondQueryMock);

        $secondQueryMock->joinRaw(
            Argument::type('callable'),
            'm.room_guid = r.room_guid',
        )
            ->shouldBeCalledOnce()
            ->willReturn($secondQueryMock);

        $secondQueryMock->where('r.tenant_id', Operator::EQ, new RawExp(":tenant_id_4"))
            ->shouldBeCalledOnce()
            ->willReturn($secondQueryMock);

        $secondQueryMock->where('r.room_type', Operator::EQ, new RawExp(":room_type_2"))
            ->shouldBeCalledOnce()
            ->willReturn($secondQueryMock);

        $secondQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($secondPdoStatementMock);

        // Set up mock for main query

        $pdoStatementMock->execute([
            'tenant_id_1' => 1,
            'tenant_id_2' => 1,
            'tenant_id_3' => 1,
            'tenant_id_4' => 1,
            'room_type_1' => ChatRoomTypeEnum::ONE_TO_ONE->name,
            'room_type_2' => ChatRoomTypeEnum::ONE_TO_ONE->name,
            'member_guid_1' => 456,
            'member_guid_2' => 789,
            'status_1' => ChatRoomMemberStatusEnum::ACTIVE->name,
            'status_2' => ChatRoomMemberStatusEnum::INVITE_PENDING->name,
            'status_3' => ChatRoomMemberStatusEnum::ACTIVE->name,
            'status_4' => ChatRoomMemberStatusEnum::INVITE_PENDING->name,
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $pdoStatementMock->rowCount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $pdoStatementMock->fetch(PDO::FETCH_ASSOC)
            ->shouldBeCalledOnce()
            ->willReturn([
                'room_guid' => 123,
                'room_type' => ChatRoomTypeEnum::ONE_TO_ONE->name,
                'created_by_user_guid' => 456,
                'created_timestamp' => '2021-01-01 00:00:00',
                'group_guid' => null
            ]);

        $selectQueryMock->columns([
            'r.*'
        ])
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->from(
            Argument::that(
                function (RawExp $exp) use ($firstQueryString): bool {
                    return $exp->getValue() === "($firstQueryString) as r";
                }
            )
        )
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->innerJoin(
            new RawExp("($secondQueryString) as r2"),
            'r.room_guid',
            Operator::EQ,
            'r2.room_guid'
        )
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientReaderHandlerMock->select()
            ->shouldBeCalledTimes(3)
            ->willReturn(
                $fistQueryMock,
                $secondQueryMock,
                $selectQueryMock
            );

        $this->getOneToOneRoomByMembers(456, 789)
            ->shouldBeAnInstanceOf(ChatRoom::class);
    }

    private function getMemberOneToOneRoomsQueryString(int $parametersDifferentiator): string
    {
        return "SELECT `r`.* FROM minds_chat_rooms as r INNER JOIN (SELECT `room_guid` FROM `minds_chat_members` WHERE `tenant_id` = :tenant_id_" . ($parametersDifferentiator * 2 + 1) . " AND `member_guid` = :member_guid_" . ($parametersDifferentiator + 1) . " AND (status = :status_" . ($parametersDifferentiator * 2 + 1) . " OR status = :status_" . ($parametersDifferentiator * 2 + 2) . ") GROUP BY `room_guid`) AS `m` ON (m.room_guid = r.room_guid) WHERE `r`.`tenant_id` = :tenant_id_" . ($parametersDifferentiator * 2 + 2) . " AND `r`.`room_type` = :room_type_" . ($parametersDifferentiator + 1);
    }

    public function it_should_delete_all_room_messages(
        DeleteQuery $deleteQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $pdoStatementMock->execute([
            'tenant_id' => 1,
            'room_guid' => 123,
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $deleteQueryMock->from(RoomRepository::MESSAGES_TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientWriterHandlerMock->delete()
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $this->deleteAllRoomMessages(123)
            ->shouldEqual(true);
    }

    public function it_should_delete_all_room_message_read_receipts(
        DeleteQuery $deleteQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $pdoStatementMock->execute([
            'tenant_id' => 1,
            'room_guid' => 123,
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $deleteQueryMock->from(RoomRepository::RECEIPTS_TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientWriterHandlerMock->delete()
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $this->deleteAllRoomMessageReadReceipts(123)
            ->shouldEqual(true);
    }

    public function it_should_delete_all_room_members_settings(
        DeleteQuery $deleteQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $pdoStatementMock->execute([
            'tenant_id' => 1,
            'room_guid' => 123,
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $deleteQueryMock->from(RoomRepository::ROOM_MEMBER_SETTINGS_TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientWriterHandlerMock->delete()
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $this->deleteAllRoomMembersSettings(123)
            ->shouldEqual(true);
    }

    public function it_should_delete_all_room_members(
        DeleteQuery $deleteQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $pdoStatementMock->execute([
            'tenant_id' => 1,
            'room_guid' => 123,
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $deleteQueryMock->from(RoomRepository::MEMBERS_TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientWriterHandlerMock->delete()
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $this->deleteAllRoomMembers(123)
            ->shouldEqual(true);
    }

    public function it_should_delete_room(
        DeleteQuery $deleteMessagesMock,
        DeleteQuery $deleteMessageReceiptsMock,
        DeleteQuery $deleteMembersSettingsMock,
        DeleteQuery $deleteMembersMock,
        DeleteQuery $deleteRoomMock,
        PDOStatement $pdoStatementMock
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledTimes(5)->willReturn(1);

        $pdoStatementMock->execute([
            'tenant_id' => 1,
            'room_guid' => 123,
        ])
            ->shouldBeCalledTimes(5)
            ->willReturn(true);

        // Delete message read receipts
        $deleteMessageReceiptsMock->from(RoomRepository::RECEIPTS_TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($deleteMessageReceiptsMock);

        $deleteMessageReceiptsMock->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteMessageReceiptsMock);

        $deleteMessageReceiptsMock->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteMessageReceiptsMock);

        $deleteMessageReceiptsMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        // Delete messages
        $deleteMessagesMock->from(RoomRepository::MESSAGES_TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($deleteMessagesMock);

        $deleteMessagesMock->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteMessagesMock);

        $deleteMessagesMock->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteMessagesMock);

        $deleteMessagesMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        // Delete members settings
        $deleteMembersSettingsMock->from(RoomRepository::ROOM_MEMBER_SETTINGS_TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($deleteMembersSettingsMock);

        $deleteMembersSettingsMock->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteMembersSettingsMock);

        $deleteMembersSettingsMock->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteMembersSettingsMock);

        $deleteMembersSettingsMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        // Delete members
        $deleteMembersMock->from(RoomRepository::MEMBERS_TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($deleteMembersMock);

        $deleteMembersMock->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteMembersMock);

        $deleteMembersMock->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteMembersMock);

        $deleteMembersMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        // Delete room
        $deleteRoomMock->from(RoomRepository::TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($deleteRoomMock);

        $deleteRoomMock->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteRoomMock);

        $deleteRoomMock->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteRoomMock);

        $deleteRoomMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientWriterHandlerMock->delete()
            ->shouldBeCalledTimes(5)
            ->willReturn(
                $deleteMessageReceiptsMock,
                $deleteMessagesMock,
                $deleteMembersSettingsMock,
                $deleteMembersMock,
                $deleteRoomMock
            );

        $this->deleteRoom(123)
            ->shouldEqual(true);
    }

    public function it_should_return_true_when_user_IS_room_owner(
        SelectQuery $selectQueryMock,
        PDOStatement $pdoStatementMock,
        User $userMock
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $userMock->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn(456);

        $pdoStatementMock->execute([
            'tenant_id' => 1,
            'room_guid' => 123,
            'member_guid' => "456",
            'role_id' => ChatRoomRoleEnum::OWNER->name,
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $pdoStatementMock->rowCount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $selectQueryMock->from(RoomRepository::MEMBERS_TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('member_guid', Operator::EQ, new RawExp(':member_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('role_id', Operator::EQ, new RawExp(':role_id'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->limit(1)
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientReaderHandlerMock->select()
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $this->isUserRoomOwner(
            123,
            $userMock
        )
            ->shouldEqual(true);
    }

    public function it_should_add_room_member_default_settings(
        InsertQuery $insertQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $pdoStatementMock->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $insertQueryMock->into(RoomRepository::ROOM_MEMBER_SETTINGS_TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->set([
            'tenant_id' => 1,
            'room_guid' => 123,
            'member_guid' => 456,
            'notifications_status' => ChatRoomNotificationStatusEnum::ALL->value,
        ])
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientWriterHandlerMock->insert()
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $this->addRoomMemberDefaultSettings(
            123,
            456,
            ChatRoomNotificationStatusEnum::ALL
        )
            ->shouldEqual(true);
    }

    public function it_should_update_room_member_settings(
        UpdateQuery $updateQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $pdoStatementMock->execute([
            'tenant_id' => 1,
            'room_guid' => 123,
            'member_guid' => 456,
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $updateQueryMock->table(RoomRepository::ROOM_MEMBER_SETTINGS_TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($updateQueryMock);

        $updateQueryMock->set([
            'notifications_status' => ChatRoomNotificationStatusEnum::ALL->value,
        ])
            ->shouldBeCalledOnce()
            ->willReturn($updateQueryMock);

        $updateQueryMock->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($updateQueryMock);

        $updateQueryMock->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($updateQueryMock);

        $updateQueryMock->where('member_guid', Operator::EQ, new RawExp(':member_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($updateQueryMock);

        $updateQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientWriterHandlerMock->update()
            ->shouldBeCalledOnce()
            ->willReturn($updateQueryMock);

        $this->updateRoomMemberSettings(
            123,
            456,
            ChatRoomNotificationStatusEnum::ALL
        )
            ->shouldEqual(true);
    }

    public function it_should_delete_room_member_settings(
        DeleteQuery $deleteQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $pdoStatementMock->execute([
            'tenant_id' => 1,
            'room_guid' => 123,
            'member_guid' => 456,
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $deleteQueryMock->from(RoomRepository::ROOM_MEMBER_SETTINGS_TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->where('member_guid', Operator::EQ, new RawExp(':member_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientWriterHandlerMock->delete()
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $this->deleteRoomMemberSettings(
            123,
            456,
            ChatRoomNotificationStatusEnum::ALL
        )
            ->shouldEqual(true);
    }
}
