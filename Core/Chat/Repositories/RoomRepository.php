<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Repositories;

use DateTimeImmutable;
use Exception;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Enums\ChatRoomMemberStatusEnum;
use Minds\Core\Chat\Enums\ChatRoomRoleEnum;
use Minds\Core\Chat\Enums\ChatRoomTypeEnum;
use Minds\Core\Chat\Exceptions\RoomNotFoundException;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOException;
use Selective\Database\Operator;

class RoomRepository extends AbstractRepository
{
    private const TABLE_NAME = 'minds_chat_rooms';
    private const MEMBERS_TABLE_NAME = 'minds_chat_members';

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
     * @throws RoomNotFoundException
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
                throw new RoomNotFoundException();
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
}
