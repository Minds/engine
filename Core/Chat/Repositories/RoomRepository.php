<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Repositories;

use DateTimeImmutable;
use Exception;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Entities\ChatRoomListItem;
use Minds\Core\Chat\Enums\ChatRoomMemberStatusEnum;
use Minds\Core\Chat\Enums\ChatRoomNotificationStatusEnum;
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
    public const MESSAGES_TABLE_NAME = 'minds_chat_messages';
    public const RECEIPTS_TABLE_NAME = 'minds_chat_receipts';
    public const ROOM_MEMBER_SETTINGS_TABLE_NAME = 'minds_chat_room_member_settings';
    public const RICH_EMBED_TABLE_NAME = 'minds_chat_rich_embeds';

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
                'tenant_id' => $this->getTenantId(),
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
                'tenant_id' => $this->getTenantId(),
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
     * @param int|null $lastMessageCreatedAtTimestamp
     * @param int|null $roomCreatedAtTimestamp
     * @param int|null $roomGuid
     * @return array{chatRooms: ChatRoomListItem[], hasMore: bool}
     * @throws ServerErrorException
     */
    public function getRoomsByMember(
        User         $user,
        ?array       $targetMemberStatuses = null,
        int          $limit = 12,
        ?int         $lastMessageCreatedAtTimestamp = null,
        ?int         $roomCreatedAtTimestamp = null,
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
                "), // For temporary performance gains, we will just return a maximum count of 1
                'member_guids' => 'm_guids.member_guids',
            ])
            ->from(new RawExp(self::TABLE_NAME . " as r"))
            // Inner join against the membership union
            ->joinRaw(
                function (SelectQuery $subQuery): void {
                    $q = $this->buildRoomMembershipQuery()->build(false);

                    $subQuery->columns([
                        'tenant_id',
                        'room_guid',
                        'member_guid',
                        'status',
                    ])
                    ->from(new RawExp("($q) as m"))
                    ->alias('m');
                },
                'r.room_guid = m.room_guid AND r.tenant_id = m.tenant_id AND m.member_guid = :member_guid',
            )
            // Get last message
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
            // Get the read receipts
            ->leftJoinRaw(
                new RawExp(ReceiptRepository::TABLE_NAME . " as rct"),
                'r.room_guid = rct.room_guid AND r.tenant_id = rct.tenant_id AND rct.member_guid = m.member_guid',
            )
            // Get guids of group members (excluding group owned)
            ->leftJoinRaw(
                function (SelectQuery $subQuery): void {
                    $subQuery->columns([
                        'tenant_id',
                        'room_guid',
                        'member_guids' => new RawExp('GROUP_CONCAT(member_guid)'),
                    ])
                    ->from(self::MEMBERS_TABLE_NAME)
                    ->groupBy('tenant_id', 'room_guid')
                    ->alias('m_guids');
                },
                'r.room_guid = m_guids.room_guid AND r.tenant_id = m_guids.tenant_id',
            )
            ->where('r.tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->whereWithNamedParameters('m.status', Operator::IN, 'status', count($targetMemberStatuses))
            ->orderBy('last_msg.created_timestamp DESC', 'r.created_timestamp DESC')
            ->limit($limit + 1);

        if ($roomGuid) {
            $stmt->where('r.room_guid', Operator::EQ, $roomGuid);
        }

        $optionalValues = [];
        if ($lastMessageCreatedAtTimestamp) {
            $stmt->whereRaw('(last_msg.created_timestamp < :last_msg_created_timestamp OR last_msg.created_timestamp IS NULL)');
            $optionalValues = [
                'last_msg_created_timestamp' => date('c', $lastMessageCreatedAtTimestamp),
            ];
        } elseif ($roomCreatedAtTimestamp) {
            $stmt->where('last_msg.created_timestamp', Operator::IS, null);
            $stmt->where('r.created_timestamp', Operator::LT, new RawExp(':created_timestamp'));
            $optionalValues = [
                'created_timestamp' => date('c', $roomCreatedAtTimestamp),
            ];
        }

        $stmt = $stmt
            ->prepare();

        try {
            $this->mysqlHandler->bindValuesToPreparedStatement(
                $stmt,
                array_merge(
                    [
                        'tenant_id' => $this->getTenantId(),
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
                    memberGuids: $row['member_guids'] ? explode(',', $row['member_guids']) : [],
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
                    'tenant_id' => $this->getTenantId(),
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

        $query = $this->buildRoomMembershipQuery()
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->where('member_guid', Operator::EQ, new RawExp(':member_guid'))
            ->whereWithNamedParameters('status', Operator::IN, 'status', count($targetStatuses));

        $stmt = $query->prepare();

        try {
            $this->mysqlHandler->bindValuesToPreparedStatement($stmt, [
                'tenant_id' => $this->getTenantId(),
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
     * Note: groups do not call this as its only used for checking invite status
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
                'tenant_id' => $this->getTenantId(),
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
     * @param int|null $offsetJoinedTimestamp
     * @param int|null $offsetMemberGuid
     * @param bool $excludeSelf
     * @return array{members: array{member_guid: int, joined_timestamp: int|null}, hasMore: bool}
     * @throws ServerErrorException
     */
    public function getRoomMembers(
        int  $roomGuid,
        User $user,
        int  $limit = 12,
        ?int $offsetJoinedTimestamp = null,
        ?int $offsetMemberGuid = null,
        bool $excludeSelf = true
    ): array {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->columns([
                'm.*',
                'rms.notifications_status'
            ])
            ->from(new RawExp(self::MEMBERS_TABLE_NAME . ' as m'))
            ->joinRaw(
                new RawExp(self::ROOM_MEMBER_SETTINGS_TABLE_NAME . ' as rms'),
                'rms.tenant_id = m.tenant_id AND rms.member_guid = m.member_guid AND rms.room_guid = m.room_guid',
            )
            ->where('m.tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('m.room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->whereWithNamedParameters('m.status', Operator::IN, 'status', 2)
            ->orderBy('m.joined_timestamp ASC', 'm.member_guid DESC')
            ->limit($limit + 1);

        $values = [
            'tenant_id' => $this->getTenantId(),
            'room_guid' => $roomGuid,
            'status' => [ChatRoomMemberStatusEnum::ACTIVE->name, ChatRoomMemberStatusEnum::INVITE_PENDING->name],
        ];

        if ($excludeSelf) {
            $stmt->where('m.member_guid', Operator::NOT_EQ, new RawExp(':member_guid'));
            $values['member_guid'] = $user->getGuid();
        }

        if ($offsetJoinedTimestamp) {
            $stmt->where('joined_timestamp', Operator::GT, new RawExp(':joined_timestamp'));
            $values['joined_timestamp'] = date('c', $offsetJoinedTimestamp);
        } elseif ($offsetMemberGuid) {
            $stmt->where('joined_timestamp', Operator::IS, null);
            $stmt->where('member_guid', Operator::LT, new RawExp(':member_guid'));
            $values['member_guid'] = date('c', $offsetMemberGuid);
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
     * @param int $roomGuid
     * @param User $user
     * @param bool $excludeSelf
     * @return iterable<array{
     *     tenant_id: int,
     *     room_guid: int,
     *     member_guid: int,
     *     joined_timestamp: ?string,
     *     role_id: ChatRoomRoleEnum,
     *     status: ChatRoomMemberStatusEnum,
     *     notifications_status: ChatRoomNotificationStatusEnum,
     *  }>
     * @throws ServerErrorException
     */
    public function getAllRoomMembers(
        int $roomGuid,
        User $user,
        bool $excludeSelf = true
    ): iterable {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->columns([
                'm.*',
                'rms.notifications_status'
            ])
            ->from(new RawExp(self::MEMBERS_TABLE_NAME . ' as m'))
            ->joinRaw(
                new RawExp(self::ROOM_MEMBER_SETTINGS_TABLE_NAME . ' as rms'),
                'rms.tenant_id = m.tenant_id AND rms.member_guid = m.member_guid AND rms.room_guid = m.room_guid',
            )
            ->where('m.tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('m.room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->whereWithNamedParameters('m.status', Operator::IN, 'status', 2)
            ->orderBy('m.joined_timestamp ASC', 'm.member_guid DESC');

        $values = [
            'tenant_id' => $this->getTenantId(),
            'room_guid' => $roomGuid,
            'status' => [ChatRoomMemberStatusEnum::ACTIVE->name, ChatRoomMemberStatusEnum::INVITE_PENDING->name],
        ];

        if ($excludeSelf) {
            $stmt->where('m.member_guid', Operator::NOT_EQ, new RawExp(':member_guid'));
            $values['member_guid'] = $user->getGuid();
        }

        $stmt = $stmt->prepare();

        try {
            $this->mysqlHandler->bindValuesToPreparedStatement($stmt, $values);
            $stmt->execute();

            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            return $stmt->getIterator();
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
                'tenant_id' => $this->getTenantId(),
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
            'tenant_id' => $this->getTenantId(),
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
        $firstMemberGuidOneToOneRoomQuery = trim(
            $this->getMemberOneToOneRoomsQuery(0)->prepare()->queryString,
            ';'
        );
        $secondMemberGuidOneToOneRoomQuery = trim(
            $this->getMemberOneToOneRoomsQuery(1)->prepare()->queryString,
            ';'
        );

        $stmt = $this->mysqlClientReaderHandler->select()
            ->columns([
                'r.*'
            ])
            ->from(new RawExp("($firstMemberGuidOneToOneRoomQuery) as r"))
            ->innerJoin(
                new RawExp("($secondMemberGuidOneToOneRoomQuery) as r2"),
                'r.room_guid',
                Operator::EQ,
                'r2.room_guid'
            )->prepare();

        

        try {
            $stmt->execute([
                'tenant_id_1' => $this->getTenantId(),
                'tenant_id_2' => $this->getTenantId(),
                'tenant_id_3' => $this->getTenantId(),
                'tenant_id_4' => $this->getTenantId(),
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
     * Return all the rooms associated with a group
     * @return ChatRoom[]
     */
    public function getGroupRooms(int $groupGuid): array
    {
        $query = $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->where('group_guid', Operator::EQ, new RawExp(':group_guid'))
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'));

        $stmt = $query->prepare();

        $stmt->execute([
            'group_guid' => $groupGuid,
            'tenant_id' => $this->getTenantId(),
        ]);

        $rooms = [];
    
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rooms[] = $this->buildChatRoomInstance($row);
        }

        return $rooms;
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
                'tenant_id' => $this->getTenantId(),
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
                'tenant_id' => $this->getTenantId(),
                'room_guid' => $roomGuid,
            ]);
        } catch (PDOException $e) {
            throw new ServerErrorException(message: 'Failed to delete chat room message read receipts', previous: $e);
        }
    }

    /**
     * Delete all rich embeds in a room.
     * @param int $roomGuid - the room guid.
     * @return bool true on success.
     * @throws ServerErrorException on failure.
     */
    public function deleteAllRoomRichEmbeds(
        int $roomGuid
    ): bool {
        $stmt = $this->mysqlClientWriterHandler->delete()
            ->from(self::RICH_EMBED_TABLE_NAME)
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->prepare();

        try {
            return $stmt->execute([
                'tenant_id' => $this->getTenantId(),
                'room_guid' => $roomGuid,
            ]);
        } catch (PDOException $e) {
            throw new ServerErrorException(message: 'Failed to delete chat room rich embeds', previous: $e);
        }
    }

    /**
     * @param int $roomGuid
     * @return bool
     * @throws ServerErrorException
     */
    public function deleteAllRoomMembersSettings(
        int $roomGuid
    ): bool {
        $stmt = $this->mysqlClientWriterHandler->delete()
            ->from(self::ROOM_MEMBER_SETTINGS_TABLE_NAME)
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->prepare();

        try {
            return $stmt->execute([
                'tenant_id' => $this->getTenantId(),
                'room_guid' => $roomGuid,
            ]);
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to delete chat room members settings', previous: $e);
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
                'tenant_id' => $this->getTenantId(),
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

        $this->deleteAllRoomRichEmbeds(
            roomGuid: $roomGuid
        );

        $this->deleteAllRoomMessages(
            roomGuid: $roomGuid
        );

        $this->deleteAllRoomMembersSettings(
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
                'tenant_id' => $this->getTenantId(),
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
                'tenant_id' => $this->getTenantId(),
                'room_guid' => $roomGuid,
                'member_guid' => $user->getGuid(),
                'role_id' => ChatRoomRoleEnum::OWNER->name,
            ]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new ServerErrorException(message: 'Failed to check if user is room owner', previous: $e);
        }
    }

    /**
     * @param int $roomGuid
     * @param int $memberGuid
     * @param ChatRoomNotificationStatusEnum $notificationStatus
     * @return bool
     * @throws ServerErrorException
     */
    public function addRoomMemberDefaultSettings(
        int $roomGuid,
        int $memberGuid,
        ChatRoomNotificationStatusEnum $notificationStatus
    ): bool {
        $stmt = $this->mysqlClientWriterHandler->insert()
            ->into(self::ROOM_MEMBER_SETTINGS_TABLE_NAME)
            ->set([
                'tenant_id' => $this->getTenantId(),
                'room_guid' => $roomGuid,
                'member_guid' => $memberGuid,
                'notifications_status' => $notificationStatus->value,
            ])
            ->prepare();

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to add member default settings to chat room', previous: $e);
        }
    }

    /**
     * @param int $roomGuid
     * @param int $memberGuid
     * @param ChatRoomNotificationStatusEnum $notificationStatus
     * @return bool
     * @throws ServerErrorException
     */
    public function updateRoomMemberSettings(
        int $roomGuid,
        int $memberGuid,
        ChatRoomNotificationStatusEnum $notificationStatus
    ): bool {
        $query = $this->mysqlClientWriterHandler->insert()
            ->into(self::ROOM_MEMBER_SETTINGS_TABLE_NAME)
            ->set([
                'tenant_id' => new RawExp(':tenant_id'),
                'room_guid' => new RawExp(':room_guid'),
                'member_guid' => new RawExp(':member_guid'),
                'notifications_status' => $notificationStatus->value,
            ])
            ->onDuplicateKeyUpdate([
                'notifications_status' =>  $notificationStatus->value,
            ]);

        $stmt = $query->prepare();

        try {
            return $stmt->execute([
                'tenant_id' => $this->getTenantId(),
                'room_guid' => $roomGuid,
                'member_guid' => $memberGuid,
            ]);
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to update member settings in chat room', previous: $e);
        }
    }

    /**
     * @param int $roomGuid
     * @param int $memberGuid
     * @return bool
     * @throws ServerErrorException
     */
    public function deleteRoomMemberSettings(
        int $roomGuid,
        int $memberGuid
    ): bool {
        $stmt = $this->mysqlClientWriterHandler->delete()
            ->from(self::ROOM_MEMBER_SETTINGS_TABLE_NAME)
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->where('member_guid', Operator::EQ, new RawExp(':member_guid'))
            ->prepare();

        try {
            return $stmt->execute([
                'tenant_id' => $this->getTenantId(),
                'room_guid' => $roomGuid,
                'member_guid' => $memberGuid,
            ]);
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to delete member settings in chat room', previous: $e);
        }
    }

    /**
     * @param int $roomGuid
     * @param int $memberGuid
     * @return array{notifications_status: ChatRoomNotificationStatusEnum}
     * @throws ServerErrorException
     */
    public function getRoomMemberSettings(
        int $roomGuid,
        int $memberGuid
    ): array {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->from(self::ROOM_MEMBER_SETTINGS_TABLE_NAME)
            ->columns([
                'notifications_status'
            ])
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->where('member_guid', Operator::EQ, new RawExp(':member_guid'))
            ->limit(1)
            ->prepare();

        try {
            $stmt->execute([
                'tenant_id' => $this->getTenantId(),
                'room_guid' => $roomGuid,
                'member_guid' => $memberGuid,
            ]);

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to fetch member settings in chat room', previous: $e);
        }
    }

    /**
     * Build a query that returns both the one-to-one, multi-user and group owned
     * chat room memberships.
     */
    private function buildRoomMembershipQuery(): SelectQuery
    {
        $membersQuery = $this->mysqlClientReaderHandler->select()
            ->columns([
                'tenant_id',
                'room_guid',
                'member_guid',
                'status',
            ])
            ->from(self::MEMBERS_TABLE_NAME);

        $groupsQuery = $this->mysqlClientReaderHandler->select()
            ->columns([
                'tenant_id',
                'room_guid',
                'member_guid' => 'user_guid',
                'status' => new RawExp('"' . ChatRoomMemberStatusEnum::ACTIVE->name . '"'),
            ])
            ->from(new RawExp(self::TABLE_NAME . ' as r'))
            ->joinRaw(new RawExp('minds_group_membership as gm'), 'r.group_guid = gm.group_guid');

        return $membersQuery->union($groupsQuery);
    }

    /**
     * Returns the tenant id, or -1 if host site
     */
    private function getTenantId(): int
    {
        return $this->config->get('tenant_id') ?? -1;
    }
}
