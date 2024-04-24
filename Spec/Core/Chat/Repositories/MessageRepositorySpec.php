<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Chat\Repositories;

use DateTime;
use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Entities\ChatRichEmbed;
use Minds\Core\Chat\Enums\ChatMessageTypeEnum;
use Minds\Core\Chat\Repositories\MessageRepository;
use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Log\Logger;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Selective\Database\Connection;
use Selective\Database\DeleteQuery;
use Selective\Database\InsertQuery;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;

class MessageRepositorySpec extends ObjectBehavior
{
    private Collaborator $mysqlHandlerMock;
    private Collaborator $mysqlClientWriterHandlerMock;
    private Collaborator $mysqlClientReaderHandlerMock;
    private Collaborator $loggerMock;
    private Collaborator $configMock;

    public function let(
        MySQLClient $mysqlClient,
        Logger      $logger,
        Config      $config,
        Connection  $mysqlMasterConnectionHandler,
        Connection  $mysqlReaderConnectionHandler,
        PDO         $mysqlMasterConnection,
        PDO         $mysqlReaderConnection,
    ): void {
        $this->mysqlHandlerMock = $mysqlClient;

        $this->mysqlHandlerMock->getConnection(MySQLClient::CONNECTION_MASTER)
            ->willReturn($mysqlMasterConnection);
        $mysqlMasterConnectionHandler->getPdo()->willReturn($mysqlMasterConnection);
        $this->mysqlClientWriterHandlerMock = $mysqlMasterConnectionHandler;

        $this->mysqlHandlerMock->getConnection(MySQLClient::CONNECTION_REPLICA)
            ->willReturn($mysqlReaderConnection);
        $mysqlReaderConnectionHandler->getPdo()->willReturn($mysqlReaderConnection);
        $this->mysqlClientReaderHandlerMock = $mysqlReaderConnectionHandler;

        $this->loggerMock = $logger;
        $this->configMock = $config;

        $this->beConstructedThrough('buildForUnitTests', [
            $this->mysqlHandlerMock->getWrappedObject(),
            $this->configMock->getWrappedObject(),
            $this->loggerMock->getWrappedObject(),
            $this->mysqlClientWriterHandlerMock->getWrappedObject(),
            $this->mysqlClientReaderHandlerMock->getWrappedObject(),
        ]);
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(MessageRepository::class);
    }

    public function it_should_add_message(
        InsertQuery $insertQueryMock,
        PDOStatement $pdoStatementMock,
    ): void {
        $messageMock = $this->generateChatMessageMock(
            roomGuid: 1,
            messageGuid: 123,
            senderGuid: 456,
            plainText: 'Hello, World!',
            messageType: ChatMessageTypeEnum::RICH_EMBED
        );
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $pdoStatementMock->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $insertQueryMock->into(MessageRepository::TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->set([
            'tenant_id' => 1,
            'room_guid' => $messageMock->roomGuid,
            'guid' => $messageMock->guid,
            'sender_guid' => $messageMock->senderGuid,
            'plain_text' => $messageMock->plainText,
            'message_type' => $messageMock->messageType->name
        ])
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientWriterHandlerMock->insert()
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $this->addMessage($messageMock)->shouldReturn(true);
    }

    public function it_should_get_messages_by_room(
        SelectQuery $selectQueryMock,
        PDOStatement $pdoStatementMock,
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $pdoStatementMock->execute([
            'tenant_id' => 1,
            'room_guid' => 123
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $pdoStatementMock->rowCount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->shouldBeCalledOnce()
            ->willReturn([
                [
                    'room_guid' => 123,
                    'guid' => 456,
                    'sender_guid' => 789,
                    'plain_text' => 'Hello, World!',
                    'message_type' => 'TEXT',
                    'created_timestamp' => '2021-01-01 00:00:00'
                ]
            ]);

        $selectQueryMock->from(MessageRepository::TABLE_NAME . ' as m')
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->columns([
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
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('m.tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('m.room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->leftJoinRaw(
            new RawExp(MessageRepository::RICH_EMBED_TABLE_NAME.' as re'),
            're.tenant_id = m.tenant_id AND re.room_guid = m.room_guid AND re.message_guid = m.guid'
        )
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->limit(13)
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->orderBy('guid DESC')
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientReaderHandlerMock->select()
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $this->getMessagesByRoom(
            123,
            12,
            null,
            null,
        )->shouldBeArray();
    }

    public function it_should_get_message_by_room_and_message_guid(
        SelectQuery $selectQueryMock,
        PDOStatement $pdoStatementMock,
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $pdoStatementMock->execute([
            'tenant_id' => 1,
            'room_guid' => 123,
            'message_guid' => 456
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $pdoStatementMock->rowCount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $pdoStatementMock->fetch(PDO::FETCH_ASSOC)
            ->shouldBeCalledOnce()
            ->willReturn([
                'room_guid' => 123,
                'guid' => 456,
                'sender_guid' => 789,
                'plain_text' => 'Hello, World!',
                'message_type' => 'TEXT',
                'created_timestamp' => '2021-01-01 00:00:00'
            ]);

        $selectQueryMock->from(MessageRepository::TABLE_NAME . ' as m')
        ->shouldBeCalledOnce()
        ->willReturn($selectQueryMock);

        $selectQueryMock->columns([
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
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('m.tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('m.room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->leftJoinRaw(
            new RawExp(MessageRepository::RICH_EMBED_TABLE_NAME.' as re'),
            're.tenant_id = m.tenant_id AND re.room_guid = m.room_guid AND re.message_guid = m.guid'
        )
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('guid', Operator::EQ, new RawExp(':message_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientReaderHandlerMock->select()
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $this->getMessageByGuid(
            123,
            456
        )
            ->shouldBeAnInstanceOf(ChatMessage::class);
    }

    public function it_should_delete_message_by_room_and_message_guid(
        DeleteQuery $deleteQueryMock,
        PDOStatement $pdoStatementMock,
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $pdoStatementMock->execute([
            'tenant_id' => 1,
            'room_guid' => 123,
            'message_guid' => 456
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $deleteQueryMock->from(MessageRepository::TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->where('guid', Operator::EQ, new RawExp(':message_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientWriterHandlerMock->delete()
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $this->deleteChatMessage(
            123,
            456
        )
            ->shouldEqual(true);
    }

    public function it_should_insert_rich_embed_by_chat_message(
        InsertQuery $insertQueryMock,
        PDOStatement $pdoStatementMock,
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $richEmbed = new ChatRichEmbed(
            url: 'https://example.com',
            canonicalUrl: 'https://example.com',
            title: 'Example',
            description: 'An example',
            author: 'John Doe',
            thumbnailSrc: 'https://example.com/image.jpg',
            createdTimestamp: new DateTime('now'),
            updatedTimestamp: new DateTime('now')
        );

        $chatMessage = new ChatMessage(
            roomGuid: 123,
            guid: 456,
            senderGuid: 789,
            plainText: 'Hello, World!',
            richEmbed: $richEmbed
        );

        $pdoStatementMock->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $insertQueryMock->into(MessageRepository::RICH_EMBED_TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->set([
            'tenant_id' => 1,
            'room_guid' => $chatMessage->roomGuid,
            'message_guid' => $chatMessage->guid,
            'url' => $richEmbed->url,
            'canonincal_url' => $richEmbed->canonicalUrl,
            'title' => $richEmbed->title,
            'description' => $richEmbed->description,
            'author' => $richEmbed->author,
            'thumbnail_src' => $richEmbed->thumbnailSrc
        ])
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientWriterHandlerMock->insert()
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $this->addRichEmbed($chatMessage->roomGuid, $chatMessage->guid, $richEmbed)
            ->shouldEqual(true);
    }

    public function it_should_delete_a_rich_embed_by_chat_message(
        DeleteQuery $deleteQueryMock,
        PDOStatement $pdoStatementMock,
    ): void {
        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $tenantId = 1;
        $roomGuid = 123;
        $messageGuid = 456;
        
        $pdoStatementMock->execute([
            'tenant_id' => $tenantId,
            'room_guid' => $roomGuid,
            'message_guid' => $messageGuid
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $deleteQueryMock->from(MessageRepository::TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->where('guid', Operator::EQ, new RawExp(':message_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientWriterHandlerMock->delete()
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $this->deleteChatMessage(
            $roomGuid,
            $messageGuid
        )
            ->shouldEqual(true);
    }

    private function generateChatMessageMock(
        int $roomGuid,
        int $messageGuid,
        int $senderGuid,
        string $plainText,
        ChatMessageTypeEnum $messageType = ChatMessageTypeEnum::TEXT,
    ): ChatMessage {
        return new ChatMessage(
            roomGuid: $roomGuid,
            guid: $messageGuid,
            senderGuid: $senderGuid,
            plainText: $plainText,
            messageType: $messageType,
        );
    }
}
