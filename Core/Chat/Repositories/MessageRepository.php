<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Repositories;

use DateTimeImmutable;
use Error;
use Exception;
use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Exceptions\ChatMessageNotFoundException;
use Minds\Core\Chat\Entities\ChatRichEmbed;
use Minds\Core\Chat\Enums\ChatMessageTypeEnum;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOException;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;

class MessageRepository extends AbstractRepository
{
    public const TABLE_NAME = 'minds_chat_messages';
    public const RICH_EMBED_TABLE_NAME = 'minds_chat_rich_embeds';

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
                'plain_text' => $message->plainText,
                'message_type' => $message->messageType->name
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
        $stmt = $this->getMessageBaseQuery()
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
     * @param int $roomGuid
     * @param int $messageGuid
     * @return ChatMessage
     * @throws ChatMessageNotFoundException
     * @throws ServerErrorException
     */
    public function getMessageByGuid(
        int $roomGuid,
        int $messageGuid
    ): ChatMessage {
        $stmt = $this->getMessageBaseQuery()
            ->where('guid', Operator::EQ, new RawExp(':message_guid'))
            ->prepare();

        try {
            $stmt->execute([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'room_guid' => $roomGuid,
                'message_guid' => $messageGuid,
            ]);

            if (!$stmt->rowCount()) {
                throw new ChatMessageNotFoundException();
            }

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $this->buildChatMessageInstance($row);
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
        $richEmbed = null;

        if ($data['rich_embed_url'] ?? null) {
            $richEmbed = new ChatRichEmbed(
                url: $data['rich_embed_url'],
                canonicalUrl: $data['rich_embed_canonical_url'],
                title: $data['rich_embed_title'],
                description: $data['rich_embed_description'],
                author: $data['rich_embed_author'],
                thumbnailSrc: $data['rich_embed_thumbnail_src'],
                createdTimestamp: new DateTimeImmutable($data['rich_embed_created_timestamp']),
                updatedTimestamp: new DateTimeImmutable($data['rich_embed_updated_timestamp'])
            );
        }

        try {
            $messageType = constant(ChatMessageTypeEnum::class . '::' . $data['message_type']);
        } catch (Error) {
            throw new ServerErrorException('Invalid message type');
        }

        return new ChatMessage(
            roomGuid: (int) $data['room_guid'],
            guid: (int) $data['guid'],
            senderGuid: (int) $data['sender_guid'],
            plainText: $data['plain_text'],
            createdAt: new DateTimeImmutable($data['created_timestamp']),
            messageType: $messageType,
            richEmbed: $richEmbed
        );
    }

    /**
     * @param int $roomGuid
     * @param int $messageGuid
     * @return bool
     * @throws ServerErrorExceptionFEnu
     */
    public function deleteChatMessage(
        int $roomGuid,
        int $messageGuid
    ): bool {
        $stmt = $this->mysqlClientWriterHandler->delete()
            ->from(self::TABLE_NAME)
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->where('guid', Operator::EQ, new RawExp(':message_guid'))
            ->prepare();

        try {
            return $stmt->execute([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'room_guid' => $roomGuid,
                'message_guid' => $messageGuid,
            ]);
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to delete chat message', previous: $e);
        }
    }

    /**
     * Add a rich embed.
     * @param int $roomGuid - The room guid.
     * @param int $messageGuid - The message guid.
     * @param ChatRichEmbed $chatRichEmbed - The chat rich embed.
     * @return bool - If the query was successful.
     * @throws ServerErrorException - If the query fails.
     */
    public function addRichEmbed(
        int $roomGuid,
        int $messageGuid,
        ChatRichEmbed $chatRichEmbed
    ): bool {
        $stmt = $this->mysqlClientWriterHandler->insert()
            ->into(self::RICH_EMBED_TABLE_NAME)
            ->set([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'room_guid' => $roomGuid,
                'message_guid' => $messageGuid,
                'url' => $chatRichEmbed->url,
                'canonincal_url' => $chatRichEmbed->canonicalUrl,
                'title' => $chatRichEmbed->title,
                'description' => $chatRichEmbed->description,
                'author' => $chatRichEmbed->author,
                'thumbnail_src' => $chatRichEmbed->thumbnailSrc
            ])
            ->prepare();

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to add chat rich embed', previous: $e);
        }
    }

    /**
     * Delete a rich embed.
     * @param int $roomGuid - The room guid.
     * @param int $messageGuid - The message guid.
     * @return bool - If the query was successful.
     * @throws ServerErrorException - If the query fails.
     */
    public function deleteRichEmbed(
        int $roomGuid,
        int $messageGuid
    ): bool {
        $stmt = $this->mysqlClientWriterHandler->delete()
            ->from(self::RICH_EMBED_TABLE_NAME)
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->where('message_guid', Operator::EQ, new RawExp(':message_guid'))
            ->prepare();

        try {
            return $stmt->execute([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'room_guid' => $roomGuid,
                'message_guid' => $messageGuid,
            ]);
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to delete chat rich embed', previous: $e);
        }
    }

    /**
     * Build and return the base query for getting messages.
     * @return SelectQuery The base query for getting messages.
     */
    private function getMessageBaseQuery(): SelectQuery
    {
        return $this->mysqlClientReaderHandler->select()
            ->from(new RawExp(self::TABLE_NAME. ' as m'))
            ->columns([
                'm.*',
                new RawExp('re.url as rich_embed_url'),
                new RawExp('re.canonincal_url as rich_embed_canonical_url'),
                new RawExp('re.title as rich_embed_title'),
                new RawExp('re.description as rich_embed_description'),
                new RawExp('re.author as rich_embed_author'),
                new RawExp('re.thumbnail_src as rich_embed_thumbnail_src'),
                new RawExp('re.created_timestamp as rich_embed_created_timestamp'),
                new RawExp('re.updated_timestamp as rich_embed_updated_timestamp')
            ])
            ->where('m.tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('m.room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->leftJoinRaw(
                new RawExp(self::RICH_EMBED_TABLE_NAME.' as re'),
                're.tenant_id = m.tenant_id AND re.room_guid = m.room_guid AND re.message_guid = m.guid'
            );
    }
}
