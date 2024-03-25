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
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOException;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;

class RoomRepository extends AbstractRepository
{
    public const TABLE_NAME = 'minds_chat_rooms';
    public const MEMBERS_TABLE_NAME = 'minds_chat_members';
    private const MESSAGES_TABLE_NAME = 'minds_chat_messages';
    private const RECEIPTS_TABLE_NAME = 'minds_chat_receipts';

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
            ->onDuplicateKeyUpdate([
                'status' => new RawExp(':status'),

            ])
            ->prepare();

        try {
            return $stmt->execute([
                'status' => $status->name,
            ]);
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to add member to chat room', previous: $e);
        }
    }

    /**
     * @param User $user
     * @param ChatRoomMemberStatusEnum[]|null $targetMemberStatuses
     * @param int $limit
     * @param string|null $offset
     * @param int|null $roomGuid
     * @return array{items: ChatRoomListItem[], hasMore: bool}
     * @throws ServerErrorException
     */
    public function getRoomsByMember(
        User         $user,
        ?array       $targetMemberStatuses = null,
        int          $limit = 12,
        ?string      $offset = null,
        ?int         $roomGuid = null
    ): array {
        $targetMemberStatuses = $targetMemberStatuses ?? [ChatRoomMemberStatusEnum::ACTIVE->name];

        $stmt = $this->mysqlClientReaderHandler->select()
            ->columns([
                'r.*',
                new RawExp('last_msg.plain_text as last_msg_plain_text'),
                new RawExp('last_msg.created_timestamp as last_msg_created_timestamp'),
                new RawExp("
                    CASE
                        WHEN
                            COALESCE(rct.message_guid, 0) < last_msg.guid
                        THEN 1
                        ELSE 0
                    END
                    AS unread_messages_count
                ") // For temporary performance gains, we will just return a maximum count of 1
            ])
            ->from(new RawExp(self::TABLE_NAME . " as r"))
            ->joinRaw(
                new RawExp(self::MEMBERS_TABLE_NAME . " as m"),
                'r.room_guid = m.room_guid AND r.tenant_id = m.tenant_id AND m.member_guid = :member_guid',
            )
            ->leftJoinRaw(
                function (SelectQuery $subQuery): void {
                    $subQuery
                        ->columns([
                            'msg.room_guid',
                            'msg.guid',
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
            ->leftJoinRaw(
                new RawExp(ReceiptRepository::TABLE_NAME . " as rct"),
                'r.room_guid = rct.room_guid AND r.tenant_id = rct.tenant_id AND rct.member_guid = m.member_guid',
            )
            ->where('r.tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->whereWithNamedParameters('m.status', Operator::IN, 'status', count($targetMemberStatuses))
            ->orderBy('last_msg.created_timestamp DESC', 'r.created_timestamp DESC')
            ->limit($limit + 1);

        if ($roomGuid) {
            $stmt->where('r.room_guid', Operator::EQ, $roomGuid);
        }

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
            $this->mysqlHandler->bindValuesToPreparedStatement(
                $stmt,
                array_merge(
                    [
                        'tenant_id' => $this->config->get('tenant_id') ?? -1,
                        'member_guid' => $user->getGuid(),
                        'status' => $targetMemberStatuses,
                    ],
                    $optionalValues
                )
            );
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                return [
                    'chatRooms' => [],
                    'hasMore' => false,
                ];
            }

            $results = [
                'chatRooms' => [],
                'hasMore' => false,
            ];

            if ($stmt->rowCount() > $limit) {
                $results['hasMore'] = true;
            }

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $index => $row) {
                if ($index === $limit) {
                    continue;
                }

                $results['chatRooms'][] =  new ChatRoomListItem(
                    chatRoom: $this->buildChatRoomInstance($row),
                    lastMessagePlainText: $row['last_msg_plain_text'],
                    lastMessageCreatedTimestamp: $row['last_msg_created_timestamp'] ? strtotime($row['last_msg_created_timestamp']) : null,
                    unreadMessagesCount: (int) $row['unread_messages_count'],
                );
            }

            return $results;
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
        $targetStatuses = [
            ChatRoomMemberStatusEnum::ACTIVE->name,
            ChatRoomMemberStatusEnum::INVITE_PENDING->name,
        ];

        $stmt = $this->mysqlClientReaderHandler->select()
            ->columns([
                new RawExp('COUNT(member_guid) as total_members')
            ])
            ->from(self::MEMBERS_TABLE_NAME)
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->whereWithNamedParameters('status', Operator::IN, 'status', count($targetStatuses))
            ->prepare();

        try {
            $this->mysqlHandler->bindValuesToPreparedStatement(
                $stmt,
                [
                    'tenant_id' => $this->config->get('tenant_id') ?? -1,
                    'room_guid' => $roomGuid,
                    'status' => $targetStatuses,
                ]
            );
            $stmt->execute();
            return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_members'];
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to fetch chat room members', previous: $e);
        }
    }

    /**
     * @param int $roomGuid
     * @param User $user
     * @param array|null $targetStatuses
     * @return bool
     * @throws ServerErrorException
     */
    public function isUserMemberOfRoom(
        int  $roomGuid,
        User $user,
        ?array $targetStatuses = null
    ): bool {
        $targetStatuses = $targetStatuses ?? [ChatRoomMemberStatusEnum::ACTIVE->name];
        $stmt = $this->mysqlClientReaderHandler->select()
            ->columns([
                'member_guid'
            ])
            ->from(self::MEMBERS_TABLE_NAME)
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->where('member_guid', Operator::EQ, new RawExp(':member_guid'))
            ->whereWithNamedParameters('status', Operator::IN, 'status', count($targetStatuses))
            ->limit(1)
            ->prepare();

        try {
            $this->mysqlHandler->bindValuesToPreparedStatement($stmt, [
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'room_guid' => $roomGuid,
                'member_guid' => $user->getGuid(),
                'status' => $targetStatuses,
            ]);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to check if user is a member of chat room', previous: $e);
        }
    }

    /**
     * @param User $user
     * @param int $roomGuid
     * @return ChatRoomMemberStatusEnum
     * @throws NotFoundException
     * @throws ServerErrorException
     */
    public function getUserStatusInRoom(
        User $user,
        int $roomGuid
    ): ChatRoomMemberStatusEnum {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->columns([
                'status'
            ])
            ->from(self::MEMBERS_TABLE_NAME)
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->where('member_guid', Operator::EQ, new RawExp(':member_guid'))
            ->limit(1)
            ->prepare();

        try {
            $stmt->execute([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'room_guid' => $roomGuid,
                'member_guid' => $user->getGuid(),
            ]);

            if ($stmt->rowCount() === 0) {
                throw new NotFoundException('You are not a member of the room.');
            }

            return constant(ChatRoomMemberStatusEnum::class . '::' . $stmt->fetch(PDO::FETCH_ASSOC)['status']);
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to fetch user status in chat room', previous: $e);
        }
    }

    /**
     * @param int $roomGuid
     * @param User $user
     * @param int $limit
     * @param int|null $offset
     * @param bool $excludeSelf
     * @return array{members: array{member_guid: int, joined_timestamp: int|null}, hasMore: bool}
     * @throws ServerErrorException
     */
    public function getRoomMembers(
        int  $roomGuid,
        User $user,
        int  $limit = 12,
        ?int $offset = null,
        bool $excludeSelf = true
    ): array {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->from(self::MEMBERS_TABLE_NAME)
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->whereWithNamedParameters('status', Operator::IN, 'status', 2)
            ->orderBy('joined_timestamp ASC')
            ->limit($limit + 1);

        $values = [
            'tenant_id' => $this->config->get('tenant_id') ?? -1,
            'room_guid' => $roomGuid,
            'status' => [ChatRoomMemberStatusEnum::ACTIVE->name, ChatRoomMemberStatusEnum::INVITE_PENDING->name],
        ];

        if ($excludeSelf) {
            $stmt->where('member_guid', Operator::NOT_EQ, new RawExp(':member_guid'));
            $values['member_guid'] = $user->getGuid();
        }

        if ($offset) {
            $stmt->where('joined_timestamp', Operator::GT, new RawExp(':joined_timestamp'));
            $values['joined_timestamp'] = date('c', $offset);
        }

        $stmt = $stmt->prepare();

        try {
            $this->mysqlHandler->bindValuesToPreparedStatement($stmt, $values);
            $stmt->execute();

            $results = [
                'members' => [],
                'hasMore' => false,
            ];

            if ($stmt->rowCount() > $limit) {
                $results['hasMore'] = true;
            }

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $index => $row) {
                if ($index === $limit) {
                    continue;
                }

                $results['members'][] = $row;
            }

            return $results;
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
                'status' => $memberStatus->name,
                'joined_timestamp' => new RawExp($memberStatus === ChatRoomMemberStatusEnum::ACTIVE ? ':joined_timestamp' : 'joined_timestamp')
            ])
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->where('member_guid', Operator::EQ, new RawExp(':member_guid'))
            ->prepare();

        $values = [
            'tenant_id' => $this->config->get('tenant_id') ?? -1,
            'room_guid' => $roomGuid,
            'member_guid' => $user->getGuid(),
        ];

        if ($memberStatus === ChatRoomMemberStatusEnum::ACTIVE) {
            $values['joined_timestamp'] = date('c');
        }

        try {
            return $stmt->execute($values);
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
        $firstMemberGuidOneToOneRoomQuery = $this->getMemberOneToOneRoomsQuery(0);
        $secondMemberGuidOneToOneRoomQuery = $this->getMemberOneToOneRoomsQuery(1);

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
                'member_guid_1' => $firstMemberGuid,
                'member_guid_2' => $secondMemberGuid,
                'status_1' => ChatRoomMemberStatusEnum::ACTIVE->name,
                'status_2' => ChatRoomMemberStatusEnum::INVITE_PENDING->name,
                'status_3' => ChatRoomMemberStatusEnum::ACTIVE->name,
                'status_4' => ChatRoomMemberStatusEnum::INVITE_PENDING->name,
            ]);

            if (!$stmt->rowCount()) {
                throw new ChatRoomNotFoundException();
            }

            return $this->buildChatRoomInstance($stmt->fetch(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            throw new ServerErrorException(message: 'Failed to fetch chat room by members', previous: $e);
        }
    }

    private function getMemberOneToOneRoomsQuery(int $parametersDifferentiator): SelectQuery
    {
        return $this->mysqlClientReaderHandler->select()
            ->columns([
                'r.*'
            ])
            ->from(new RawExp(self::TABLE_NAME . " as r"))
            ->joinRaw(
                function (SelectQuery $subQuery) use ($parametersDifferentiator): void {
                    $subQuery
                        ->columns([
                            'room_guid'
                        ])
                        ->from(self::MEMBERS_TABLE_NAME)
                        ->where('tenant_id', Operator::EQ, new RawExp(":tenant_id_" . ($parametersDifferentiator * 2 + 1)))
                        ->where('member_guid', Operator::EQ, new RawExp(':member_guid_' . ($parametersDifferentiator + 1)))
                        ->whereRaw('(status = :status_' . ($parametersDifferentiator * 2 + 1) . ' OR status = :status_' . ($parametersDifferentiator * 2 + 2) . ')')
                        ->groupBy('room_guid')
                        ->alias('m');
                },
                'm.room_guid = r.room_guid',
            )
            ->where('r.tenant_id', Operator::EQ, new RawExp(':tenant_id_' . ($parametersDifferentiator * 2 + 2)))
            ->where('r.room_type', Operator::EQ, new RawExp(':room_type_' . ($parametersDifferentiator + 1)));
    }

    /**
     * @param int $roomGuid
     * @return bool
     * @throws ServerErrorException
     */
    public function deleteAllRoomMessages(
        int $roomGuid
    ): bool {
        $stmt = $this->mysqlClientWriterHandler->delete()
            ->from(self::MESSAGES_TABLE_NAME)
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->prepare();

        try {
            return $stmt->execute([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'room_guid' => $roomGuid,
            ]);
        } catch (PDOException $e) {
            throw new ServerErrorException(message: 'Failed to delete chat room messages', previous: $e);
        }
    }

    /**
     * @param int $roomGuid
     * @return bool
     * @throws ServerErrorException
     */
    public function deleteAllRoomMessageReadReceipts(
        int $roomGuid
    ): bool {
        $stmt = $this->mysqlClientWriterHandler->delete()
            ->from(self::RECEIPTS_TABLE_NAME)
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->prepare();

        try {
            return $stmt->execute([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'room_guid' => $roomGuid,
            ]);
        } catch (PDOException $e) {
            throw new ServerErrorException(message: 'Failed to delete chat room message read receipts', previous: $e);
        }
    }

    /**
     * @param int $roomGuid
     * @return bool
     * @throws ServerErrorException
     */
    public function deleteAllRoomMembers(
        int $roomGuid
    ): bool {
        $stmt = $this->mysqlClientWriterHandler->delete()
            ->from(self::MEMBERS_TABLE_NAME)
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->prepare();

        try {
            return $stmt->execute([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'room_guid' => $roomGuid,
            ]);
        } catch (PDOException $e) {
            throw new ServerErrorException(message: 'Failed to delete chat room members', previous: $e);
        }
    }

    /**
     * @param int $roomGuid
     * @return bool
     * @throws ServerErrorException
     */
    public function deleteRoom(
        int $roomGuid
    ): bool {
        $this->deleteAllRoomMessageReadReceipts(
            roomGuid: $roomGuid
        );

        $this->deleteAllRoomMessages(
            roomGuid: $roomGuid
        );

        $this->deleteAllRoomMembers(
            roomGuid: $roomGuid
        );

        $stmt = $this->mysqlClientWriterHandler->delete()
            ->from(self::TABLE_NAME)
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->prepare();

        try {
            return $stmt->execute([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'room_guid' => $roomGuid,
            ]);
        } catch (PDOException $e) {
            throw new ServerErrorException(message: 'Failed to delete chat room', previous: $e);
        }
    }

    /**
     * @param int $roomGuid
     * @param User $user
     * @return bool
     * @throws ServerErrorException
     */
    public function isUserRoomOwner(
        int $roomGuid,
        User $user
    ): bool {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->from(self::MEMBERS_TABLE_NAME)
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->where('member_guid', Operator::EQ, new RawExp(':member_guid'))
            ->where('role_id', Operator::EQ, new RawExp(':role_id'))
            ->limit(1)
            ->prepare();

        try {
            $stmt->execute([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'room_guid' => $roomGuid,
                'member_guid' => $user->getGuid(),
                'role_id' => ChatRoomRoleEnum::OWNER->name,
            ]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new ServerErrorException(message: 'Failed to check if user is room owner', previous: $e);
        }
    }
}
