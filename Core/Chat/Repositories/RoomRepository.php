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
        int $roomGuid,
        ChatRoomTypeEnum $roomType,
        int $createdByGuid,
        DateTimeImmutable $createdAt,
        ?int $groupGuid = null
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
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->where('room_guid', Operator::EQ, $roomGuid)
            ->prepare();

        try {
            $stmt->execute();

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
            roomType: ChatRoomTypeEnum::cases()[$data['room_type']] ?? throw new Exception('Invalid room type'),
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
        int $roomGuid,
        int $memberGuid,
        ChatRoomMemberStatusEnum $status,
        ChatRoomRoleEnum $role
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
     * @return iterable<ChatRoomListItem>
     * @throws ServerErrorException
     * @throws Exception
     */
    public function getRoomsByMember(User $user): iterable
    {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->columns([
                'r.*',
                'last_msg.plain_text',
                'last_msg.created_timestamp',
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
                        ])
                        ->from(
                            function (SelectQuery $subQuery): void {
                                $subQuery
                                    ->columns([
                                         new RawExp('ROW_NUMBER() over (PARTITION BY room_guid ORDER BY created_timestamp DESC) as row_num'),
                                         'guid',
                                         'room_guid',
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
            ->where('r.tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->orderBy('last_msg.created_timestamp DESC', 'r.created_timestamp DESC')
            ->prepare();

        try {
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                return [];
            }

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                yield new ChatRoomListItem(
                    chatRoom: $this->buildChatRoomInstance($row),
                    lastMessagePlainText: $row['plain_text'],
                    lastMessageCreatedTimestamp: strtotime($row['created_timestamp'])
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
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->where('room_guid', Operator::EQ, $roomGuid)
            ->where('status', Operator::EQ, ChatRoomMemberStatusEnum::ACTIVE)
            ->prepare();

        try {
            $stmt->execute();
            return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total_members'];
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to fetch chat room members', previous: $e);
        }
    }






















}
