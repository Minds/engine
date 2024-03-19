<?php

namespace Minds\Core\Chat\Repositories;

use Minds\Core\Chat\Enums\ChatRoomMemberStatusEnum;
use Minds\Core\Data\MySQL\AbstractRepository;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class ReceiptRepository extends AbstractRepository
{
    public const TABLE_NAME = 'minds_chat_receipts';

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

    private function getTenantId(): int
    {
        return $this->config->get('tenant_id') ?: -1;
    }
}
