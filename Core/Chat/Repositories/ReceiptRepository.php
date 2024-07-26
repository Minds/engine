<?php

namespace Minds\Core\Chat\Repositories;

use Minds\Core\Chat\Enums\ChatRoomMemberStatusEnum;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Di\Di;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOException;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;

class ReceiptRepository extends AbstractRepository
{
    public const TABLE_NAME = 'minds_chat_receipts';

    public function __construct(
        private ?RoomRepository $roomRepository = null,
        ... $args
    ) {
        $this->roomRepository ??= Di::_()->get(RoomRepository::class);
        parent::__construct(...$args);
    }

    /**
     * Updates the last read message receipt to the current timestamp
     */
    public function updateReceipt(int $roomGuid, int $messageGuid, int $memberGuid): bool
    {
        $query = $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'tenant_id' => new RawExp(':tenant_id'),
                'room_guid' => new RawExp(':room_guid'),
                'message_guid' => new RawExp(':message_guid'),
                'member_guid' => new RawExp(':member_guid'),
                'last_read_timestamp' => new RawExp('CURRENT_TIMESTAMP()'),
            ])
            ->onDuplicateKeyUpdate([
                'message_guid' => new RawExp(':message_guid_2'),
                'last_read_timestamp' => new RawExp('CURRENT_TIMESTAMP()'),
            ]);

        $stmt = $query->prepare();

        return $stmt->execute([
            'tenant_id' => $this->getTenantId(),
            'room_guid' => $roomGuid,
            'message_guid' => $messageGuid,
            'message_guid_2' => $messageGuid,
            'member_guid' => $memberGuid,
        ]);
    }

    /**
     * Returns a count of all the message a user has not yet read
     */
    public function getAllUnreadMessagesCount(int $memberGuid): int
    {
        $query = $this->mysqlClientReaderHandler->select()
            ->from(new RawExp(MessageRepository::TABLE_NAME . " as msg"))
            ->columns([
                'unread_count' => new RawExp('COUNT(*)')
            ])
            ->joinRaw(
                new RawExp(RoomRepository::MEMBERS_TABLE_NAME . ' as m'),
                'msg.room_guid = m.room_guid AND msg.tenant_id = m.tenant_id'
            )
            ->leftJoinRaw(
                new RawExp(self::TABLE_NAME . ' as rct'),
                'm.room_guid = rct.room_guid AND m.member_guid = rct.member_guid AND m.tenant_id = rct.tenant_id',
            )
            ->where('msg.tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('m.status', Operator::EQ, new RawExp(':member_status'))
            ->where('m.member_guid', Operator::EQ, new RawExp(':member_guid'))
            ->where(new RawExp('COALESCE(rct.message_guid, 0) < msg.guid'));

        $stmt = $query->prepare();
        $stmt->execute([
            'tenant_id' => $this->getTenantId(),
            'member_guid' => $memberGuid,
            'member_status' => ChatRoomMemberStatusEnum::ACTIVE->name,
        ]);

        return (int) $stmt->fetchAll(PDO::FETCH_ASSOC)[0]['unread_count'];
    }

    /**
     * Gets a list of the guids of users who have unread messages, along with their unread message count.
     * @param array $memberStatuses - The statuses of the members to include in the query.
     * @param int|null $createdAfterTimestamp - Only include messages created after this timestamp.
     * @return array - An array of associative arrays with the keys 'user_guid', 'unread_count'.
     */
    public function getAllUsersWithUnreadMessages(
        array $memberStatuses = [ ChatRoomMemberStatusEnum::ACTIVE, ChatRoomMemberStatusEnum::INVITE_PENDING ],
        int $createdAfterTimestamp = null
    ): array {
        $values = [ 'tenant_id' => $this->getTenantId() ];

        $query = $this->mysqlClientReaderHandler->select()
            ->columns([
                'user_guid' => new RawExp('m.member_guid'),
                'unread_count' => new RawExp('COUNT(*)'),
            ])
            ->from(new RawExp(MessageRepository::TABLE_NAME . " as msg"))
            // Inner join against the membership union
            ->joinRaw(
                function (SelectQuery $subQuery): void {
                    $q = $this->roomRepository->buildRoomMembershipQuery()->build(false);

                    $subQuery->columns([
                        'tenant_id',
                        'room_guid',
                        'member_guid',
                        'status',
                        'role_id',
                    ])
                        ->from(new RawExp("($q) as m"))
                        ->alias('m');
                },
                'msg.room_guid = m.room_guid AND msg.tenant_id = m.tenant_id',
            )
            ->leftJoinRaw(
                new RawExp(self::TABLE_NAME . ' as rct'),
                'm.room_guid = rct.room_guid AND m.member_guid = rct.member_guid AND m.tenant_id = rct.tenant_id',
            )
            ->where('msg.tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('m.status', Operator::IN, array_map(fn ($status) => $status->name, $memberStatuses))
            ->whereRaw(new RawExp('COALESCE(rct.message_guid, 0) < msg.guid'))
            ->groupBy('m.member_guid');

        if ($createdAfterTimestamp) {
            $query->where('msg.created_timestamp', Operator::GT, new RawExp(':last_message_created_after_timestamp'));
            $values['last_message_created_after_timestamp'] = date('c', $createdAfterTimestamp ?: 0);
        }

        $stmt = $query->prepare();
        $stmt->execute($values);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param int $roomGuid
     * @param int $messageGuid
     * @return bool
     * @throws ServerErrorException
     */
    public function deleteAllMessageReadReceipts(
        int $roomGuid,
        int $messageGuid
    ): bool {
        $stmt = $this->mysqlClientWriterHandler->delete()
            ->from(self::TABLE_NAME)
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->where('message_guid', Operator::EQ, new RawExp(':message_guid'))
            ->prepare();

        try {
            return $stmt->execute([
                'tenant_id' => $this->getTenantId(),
                'room_guid' => $roomGuid,
                'message_guid' => $messageGuid,
            ]);
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to delete chat message read receipts', previous: $e);
        }
    }

    private function getTenantId(): int
    {
        return $this->config->get('tenant_id') ?: -1;
    }
}
