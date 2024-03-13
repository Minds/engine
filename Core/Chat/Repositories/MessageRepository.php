<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Repositories;

use DateTimeImmutable;
use Exception;
use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOException;
use Selective\Database\Operator;

class MessageRepository extends AbstractRepository
{
    private const TABLE_NAME = 'minds_chat_messages';

    /**
     * @param ChatMessage $message
     * @return bool
     * @throws ServerErrorException
     */
    public function addMessage(ChatMessage $message): bool
    {
        $stmt = $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'room_guid' => $message->roomGuid,
                'guid' => $message->guid,
                'sender_guid' => $message->senderGuid,
                'plain_text' => $message->plainText
            ])
            ->prepare();

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to add chat message', previous: $e);
        }
    }

    /**
     * @param int $roomGuid
     * @param int $limit
     * @param string|null $after
     * @param string|null $before
     * @param bool $hasMore
     * @return iterable<ChatMessage>
     * @throws ServerErrorException
     */
    public function getMessagesByRoom(
        int $roomGuid,
        int $limit = 12,
        ?string $after = null,
        ?string $before = null,
        bool &$hasMore = false,
    ): iterable {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->where('room_guid', Operator::EQ, $roomGuid)
            ->limit($limit + 1);

        if (!$before) {
            $stmt->orderBy('created_timestamp DESC');

            if ($after) {
                $stmt->where('created_timestamp', Operator::LT, date('c', (int) $after));
            }
        } else {
            $stmt->orderBy('created_timestamp ASC');
            $stmt->where('created_timestamp', Operator::GT, date('c', (int) $before));
        }

        $stmt = $stmt->prepare();

        try {
            $stmt->execute();

            if ($stmt->rowCount() > $limit) {
                $hasMore = true;
            }

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $index => $data) {
                if ($index === $limit) {
                    break;
                }
                yield $this->buildChatMessageInstance($data);
            }
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to get chat messages', previous: $e);
        }
    }

    /**
     * @param array $data
     * @return ChatMessage
     * @throws Exception
     */
    private function buildChatMessageInstance(array $data): ChatMessage
    {
        return new ChatMessage(
            roomGuid: (int) $data['room_guid'],
            guid: (int) $data['guid'],
            senderGuid: (int) $data['sender_guid'],
            plainText: $data['plain_text'],
            createdAt: new DateTimeImmutable($data['created_timestamp'])
        );
    }
}
