<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Chat\Repositories;

use Minds\Core\Chat\Entities\ChatMessage;
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
            plainText: 'Hello, World!'
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
            'plain_text' => $messageMock->plainText
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

    private function generateChatMessageMock(
        int $roomGuid,
        int $messageGuid,
        int $senderGuid,
        string $plainText
    ): ChatMessage {
        return new ChatMessage(
            roomGuid: $roomGuid,
            guid: $messageGuid,
            senderGuid: $senderGuid,
            plainText: $plainText
        );
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
                    'created_timestamp' => '2021-01-01 00:00:00'
                ]
            ]);

        $selectQueryMock->from(MessageRepository::TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
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
                'created_timestamp' => '2021-01-01 00:00:00'
            ]);

        $selectQueryMock->from(MessageRepository::TABLE_NAME)
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('room_guid', Operator::EQ, new RawExp(':room_guid'))
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
}
