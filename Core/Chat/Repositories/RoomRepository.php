<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Repositories;

use DateTimeImmutable;
use Exception;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Entities\ChatRoomListItem;
use Minds\Core\Chat\Enums\ChatRoomMemberStatusEnum;
use Minds\Core\Chat\Enums\ChatRoomRoleEnum;
use Minds\Core\Chat\Enums\ChatRoomTypeEnum;
use Minds\Core\Chat\Exceptions\ChatRoomNotFoundException;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOException;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;

class RoomRepository extends AbstractRepository
{
    private const TABLE_NAME = 'minds_chat_rooms';
    private const MEMBERS_TABLE_NAME = 'minds_chat_members';
    private const MESSAGES_TABLE_NAME = 'minds_chat_messages';

    /**
     * @param int $roomGuid
     * @param ChatRoomTypeEnum $roomType
     * @param int $createdByGuid
     * @param DateTimeImmutable $createdAt
     * @param int|null $groupGuid
     * @return bool
     * @throws ServerErrorException
     */
    public function createRoom(
        int               $roomGuid,
        ChatRoomTypeEnum  $roomType,
        int               $createdByGuid,
        DateTimeImmutable $createdAt,
        ?int              $groupGuid = null
    ): bool {
        $stmt = $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'room_guid' => $roomGuid,
                'room_type' => $roomType->name,
                'created_by_user_guid' => $createdByGuid,
                'created_timestamp' => $createdAt->format('c'),
                'group_guid' => $groupGuid,
            ])
            ->prepare();

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to create chat room', previous: $e);
        }
    }

    /**
     * @param int $roomGuid
     * @return ChatRoom
     * @throws ChatRoomNotFoundException
     * @throws ServerErrorException
     * @throws Exception
     */
    public function getRoom(int $roomGuid): ChatRoom
    {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->prepare();

        try {
            $stmt->execute([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'room_guid' => $roomGuid,
            ]);

            if (!$stmt->rowCount()) {
                throw new ChatRoomNotFoundException();
            }

            return $this->buildChatRoomInstance($stmt->fetch(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to fetch chat room', previous: $e);
        }
    }

    /**
     * @param array $data
     * @return ChatRoom
     * @throws Exception
     */
    private function buildChatRoomInstance(array $data): ChatRoom
    {
        return new ChatRoom(
            guid: $data['room_guid'],
            roomType: constant(ChatRoomTypeEnum::class . "::{$data['room_type']}") ?? throw new Exception('Invalid room type'),
            createdByGuid: $data['created_by_user_guid'],
            createdAt: new DateTimeImmutable($data['created_timestamp']),
            groupGuid: $data['group_guid'] ?? null,
        );
    }

    /**
     * @param int $roomGuid
     * @param int $memberGuid
     * @param ChatRoomMemberStatusEnum $status
     * @param ChatRoomRoleEnum $role
     * @return bool
     * @throws ServerErrorException
     */
    public function addRoomMember(
        int                      $roomGuid,
        int                      $memberGuid,
        ChatRoomMemberStatusEnum $status,
        ChatRoomRoleEnum         $role
    ): bool {
        $stmt = $this->mysqlClientWriterHandler->insert()
            ->into(self::MEMBERS_TABLE_NAME)
            ->set([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'room_guid' => $roomGuid,
                'member_guid' => $memberGuid,
                'status' => $status->name,
                'role_id' => $role->name,
                'joined_timestamp' => ($status === ChatRoomMemberStatusEnum::ACTIVE) ? date('c') : null,
            ])
            ->prepare();

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to add member to chat room', previous: $e);
        }
    }

    /**
     * @param User $user
     * @param ChatRoomMemberStatusEnum $memberStatus
     * @param int $limit
     * @param string|null $offset
     * @param bool $hasMore
     * @return iterable<ChatRoomListItem>
     * @throws ServerErrorException
     */
    public function getRoomsByMember(
        User                     $user,
        ChatRoomMemberStatusEnum $memberStatus = ChatRoomMemberStatusEnum::ACTIVE,
        int                      $limit = 12,
        ?string                  $offset = null,
        bool                     &$hasMore = false
    ): iterable {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->columns([
                'r.*',
                new RawExp('last_msg.plain_text as last_msg_plain_text'),
                new RawExp('last_msg.created_timestamp as last_msg_created_timestamp'),
            ])
            ->from(new RawExp(self::TABLE_NAME . " as r"))
            ->joinRaw(
                new RawExp(self::MEMBERS_TABLE_NAME . " as m"),
                'r.room_guid = m.room_guid AND r.tenant_id = m.tenant_id AND m.member_guid = :member_guid AND m.status = :status',
            )
            ->leftJoinRaw(
                function (SelectQuery $subQuery): void {
                    $subQuery
                        ->columns([
                            'msg.room_guid',
                            'msg.plain_text',
                            'msg.created_timestamp',
                            'msg.tenant_id'
                        ])
                        ->from(
                            function (SelectQuery $subQuery): void {
                                $subQuery
                                    ->columns([
                                        new RawExp('ROW_NUMBER() over (PARTITION BY room_guid ORDER BY created_timestamp DESC) as row_num'),
                                        'guid',
                                        'room_guid',
                                        'tenant_id',
                                        'plain_text',
                                        'created_timestamp',
                                    ])
                                    ->from(self::MESSAGES_TABLE_NAME)
                                    ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
                                    ->alias('msg');
                            }
                        )
                        ->where('msg.row_num', Operator::EQ, 1)
                        ->alias('last_msg');
                },
                "last_msg.room_guid = r.room_guid AND last_msg.tenant_id = r.tenant_id"
            )
            ->where('r.tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->orderBy('last_msg.created_timestamp DESC', 'r.created_timestamp DESC')
            ->limit($limit + 1);

        $optionalValues = [];
        if ($offset) {
            $offsetParts = explode(':', $offset);

            if (count($offsetParts) === 1) {
                $stmt->whereRaw('(last_msg.created_timestamp < :last_msg_created_timestamp OR last_msg.created_timestamp IS NULL)');
                $optionalValues = [
                    'last_msg_created_timestamp' => date('c', (int)$offsetParts[0]),
                ];
            } else {
                $stmt->where('last_msg.created_timestamp', Operator::IS, null);
                $stmt->where('r.created_timestamp', Operator::LT, new RawExp(':created_timestamp'));
                $optionalValues = [
                    'created_timestamp' => date('c', (int)$offsetParts[1]),
                ];
            }
        }

        $stmt = $stmt
            ->prepare();

        try {
            $stmt->execute(array_merge(
                [
                    'tenant_id' => $this->config->get('tenant_id') ?? -1,
                    'member_guid' => $user->getGuid(),
                    'status' => $memberStatus->name,
                ],
                $optionalValues
            ));

            if ($stmt->rowCount() === 0) {
                return [];
            }

            if ($stmt->rowCount() > $limit) {
                $hasMore = true;
            }

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $index => $row) {
                if ($index === $limit) {
                    continue;
                }

                yield new ChatRoomListItem(
                    chatRoom: $this->buildChatRoomInstance($row),
                    lastMessagePlainText: $row['last_msg_plain_text'],
                    lastMessageCreatedTimestamp: $row['last_msg_created_timestamp'] ? strtotime($row['last_msg_created_timestamp']) : null
                );
            }
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to fetch chat rooms', previous: $e);
        }
    }

    /**
     * @param int $roomGuid
     * @return int
     * @throws ServerErrorException
     */
    public function getRoomTotalMembers(int $roomGuid): int
    {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->columns([
                new RawExp('COUNT(member_guid) as total_members')
            ])
            ->from(self::MEMBERS_TABLE_NAME)
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->where('status', Operator::EQ, new RawExp(':status'))
            ->prepare();

        try {
            $stmt->execute([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'room_guid' => $roomGuid,
                'status' => ChatRoomMemberStatusEnum::ACTIVE->name,
            ]);
            return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_members'];
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to fetch chat room members', previous: $e);
        }
    }

    /**
     * @param int $roomGuid
     * @param User $user
     * @return bool
     * @throws ServerErrorException
     */
    public function isUserMemberOfRoom(
        int  $roomGuid,
        User $user
    ): bool {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->columns([
                'member_guid'
            ])
            ->from(self::MEMBERS_TABLE_NAME)
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->where('member_guid', Operator::EQ, new RawExp(':member_guid'))
            ->where('status', Operator::EQ, new RawExp(':status'))
            ->limit(1)
            ->prepare();

        try {
            $stmt->execute([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'room_guid' => $roomGuid,
                'member_guid' => $user->getGuid(),
                'status' => ChatRoomMemberStatusEnum::ACTIVE->name,
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to check if user is a member of chat room', previous: $e);
        }
    }

    /**
     * @param int $roomGuid
     * @param User $user
     * @param int $limit
     * @param int|null $offset
     * @param bool $hasMore
     * @return iterable<array{member_guid: int, joined_timestamp: int|null}>
     * @throws ServerErrorException
     */
    public function getRoomMembers(
        int  $roomGuid,
        User $user,
        int  $limit = 12,
        ?int $offset = null,
        bool &$hasMore = false
    ): iterable {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->from(self::MEMBERS_TABLE_NAME)
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->where('member_guid', Operator::NOT_EQ, new RawExp(':member_guid'))
            ->whereWithNamedParameters('status', Operator::IN, 'status', 2)
            ->orderBy('joined_timestamp ASC')
            ->limit($limit + 1);

        $values = [
            'tenant_id' => $this->config->get('tenant_id') ?? -1,
            'room_guid' => $roomGuid,
            'member_guid' => $user->getGuid(),
            'status' => [ChatRoomMemberStatusEnum::ACTIVE->name, ChatRoomMemberStatusEnum::INVITE_PENDING->name],
        ];

        if ($offset) {
            $stmt->where('joined_timestamp', Operator::GT, new RawExp(':joined_timestamp'));
            $values['joined_timestamp'] = date('c', $offset);
        }

        $stmt = $stmt->prepare();

        try {
            $this->mysqlHandler->bindValuesToPreparedStatement($stmt, $values);
            $stmt->execute();

            if ($stmt->rowCount() > $limit) {
                $hasMore = true;
            }

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $index => $row) {
                if ($index === $limit) {
                    continue;
                }

                yield $row;
            }
        } catch (PDOException $e) {
            throw new ServerErrorException(message: 'Failed to fetch chat room members', previous: $e);
        }
    }

    /**
     * @param User $user
     * @return int
     * @throws ServerErrorException
     */
    public function getTotalRoomInviteRequestsByMember(
        User $user
    ): int {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->from(self::MEMBERS_TABLE_NAME)
            ->columns([
                new RawExp('COUNT(member_guid) as total_requests')
            ])
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('member_guid', Operator::EQ, new RawExp(':member_guid'))
            ->where('status', Operator::EQ, new RawExp(':status'))
            ->prepare();

        try {
            $stmt->execute([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'member_guid' => $user->getGuid(),
                'status' => ChatRoomMemberStatusEnum::INVITE_PENDING->name,
            ]);
            return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_requests'];
        } catch (PDOException $e) {
            throw new ServerErrorException(message: 'Failed to fetch total chat room invite requests', previous: $e);
        }
    }

    public function updateRoomMemberStatus(
        int                      $roomGuid,
        User                     $user,
        ChatRoomMemberStatusEnum $memberStatus
    ): bool {
        $stmt = $this->mysqlClientWriterHandler->update()
            ->table(self::MEMBERS_TABLE_NAME)
            ->set([
                'status' => $memberStatus->name
            ])
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->where('member_guid', Operator::EQ, $user->getGuid())
            ->prepare();

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new ServerErrorException(message: 'Failed to update chat room member status', previous: $e);
        }
    }

    /**
     * @param int $firstMemberGuid
     * @param int $secondMemberGuid
     * @return ChatRoom
     * @throws ChatRoomNotFoundException
     * @throws ServerErrorException
     */
    public function getOneToOneRoomByMembers(
        int $firstMemberGuid,
        int $secondMemberGuid,
    ): ChatRoom {
        $firstMemberGuidOneToOneRoomQuery = $this->getMemberOneToOneRoomsQuery();
        $secondMemberGuidOneToOneRoomQuery = $this->getMemberOneToOneRoomsQuery();

        $stmt = $firstMemberGuidOneToOneRoomQuery
                ->intersect($secondMemberGuidOneToOneRoomQuery)
                ->prepare();

        try {
            $stmt->execute([
                'tenant_id_1' => $this->config->get('tenant_id') ?? -1,
                'tenant_id_2' => $this->config->get('tenant_id') ?? -1,
                'tenant_id_3' => $this->config->get('tenant_id') ?? -1,
                'tenant_id_4' => $this->config->get('tenant_id') ?? -1,
                'room_type_1' => ChatRoomTypeEnum::ONE_TO_ONE->name,
                'room_type_2' => ChatRoomTypeEnum::ONE_TO_ONE->name,
                'first_member_guid' => $firstMemberGuid,
                'second_member_guid' => $secondMemberGuid,
            ]);

            if (!$stmt->rowCount()) {
                throw new ChatRoomNotFoundException();
            }

            return $this->buildChatRoomInstance($stmt->fetch(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            throw new ServerErrorException(message: 'Failed to fetch chat room by members', previous: $e);
        }
    }

    private function getMemberOneToOneRoomsQuery(): SelectQuery
    {
        return $this->mysqlClientReaderHandler->select()
            ->columns([
                'r.*'
            ])
            ->from(new RawExp(self::TABLE_NAME . " as r"))
            ->innerJoin(
                function (SelectQuery $subQuery) {
                    $subQuery
                        ->columns([
                            'room_guid'
                        ])
                        ->from(self::MEMBERS_TABLE_NAME)
                        ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id_3'))
                        ->where('member_guid', Operator::EQ, new RawExp(':second_member_guid'))
                        ->groupBy('room_guid')
                        ->alias('m');
                },
                'm.room_guid',
                Operator::EQ,
                'r.room_guid'
            )
            ->where('r.tenant_id', Operator::EQ, new RawExp(':tenant_id_4'))
            ->where('r.room_type', Operator::EQ, new RawExp(':room_type_2'));
    }

}
