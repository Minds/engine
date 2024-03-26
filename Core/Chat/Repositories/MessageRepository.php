<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Repositories;

use DateTimeImmutable;
use Exception;
use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOException;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class MessageRepository extends AbstractRepository
{
    public const TABLE_NAME = 'minds_chat_messages';

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
     * @return iterable<ChatMessage>
     * @throws ServerErrorException
     */
    public function getMessagesByRoom(
        int $roomGuid,
        int $limit = 12,
        ?string $after = null,
        ?string $before = null,
    ): array {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->limit($limit + 1);

        $values = [
            'tenant_id' => $this->config->get('tenant_id') ?? -1,
            'room_guid' => $roomGuid
        ];

        if (!$before) {
            $stmt->orderBy('guid DESC');

            if ($after) {
                $stmt->where('guid', Operator::LT, new RawExp(':guid_offset'));
                $values['guid_offset'] = (int) $after;
            }
        } else {
            $stmt->orderBy('guid ASC');
            $stmt->where('guid', Operator::GT, new RawExp(':guid_offset'));
            $values['guid_offset'] = (int) $before;
        }

        $stmt = $stmt->prepare();

        try {
            $stmt->execute($values);

            $response = [
                'messages' => [],
                'hasMore' => false
            ];

            if ($stmt->rowCount() > $limit) {
                $response['hasMore'] = true;
            }

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $index => $data) {
                if ($index === $limit) {
                    break;
                }
                $response['messages'][] = $this->buildChatMessageInstance($data);
            }

            return $response;
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to get chat messages', previous: $e);
        }
    }

    /**
     * @throws NotFoundException
     */
    public function getMessageByGuid(
        int $roomGuid,
        int $messageGuid
    ): ChatMessage {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->where('guid', Operator::EQ, new RawExp(':message_guid'))
            ->orderBy('created_timestamp DESC')
            ->prepare();

        try {
            $stmt->execute([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'room_guid' => $roomGuid,
                'message_guid' => $messageGuid,
            ]);

            if (!$stmt->rowCount()) {
                throw new NotFoundException();
            }

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->buildChatMessageInstance($rows[0]);
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
