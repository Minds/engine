<?php

namespace Spec\Minds\Core\Media\Audio;

use DateTimeImmutable;
use Minds\Common\Access;
use Minds\Core\Config\Config;
use Minds\Core\Media\Audio\AudioRepository;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Log\Logger;
use Minds\Core\Media\Audio\AudioEntity;
use PDO;
use PDOStatement;
use Selective\Database\Connection;
use Selective\Database\InsertQuery;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;
use Selective\Database\UpdateQuery;

class AudioRepositorySpec extends ObjectBehavior
{
    private Collaborator $mysqlHandlerMock;
    private Collaborator $mysqlClientWriterHandlerMock;
    private Collaborator $mysqlClientReaderHandlerMock;
    private Collaborator $loggerMock;
    private Collaborator $configMock;
    private Collaborator $mysqlMasterConnectionMock;

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
        $this->mysqlMasterConnectionMock = $mysqlMasterConnection;
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

    public function it_is_initializable()
    {
        $this->shouldHaveType(AudioRepository::class);
    }

    public function it_should_add_to_database(InsertQuery $insertMock, PDOStatement $stmtMock)
    {
        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456
        );

        $this->mysqlMasterConnectionMock->inTransaction()->willReturn(false);
        $this->mysqlMasterConnectionMock->beginTransaction()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->mysqlClientWriterHandlerMock->insert()->willReturn($insertMock);
        $insertMock->into('minds_entities')->willReturn($insertMock);
        $insertMock->set([
            'tenant_id' => -1,
            'guid' => new RawExp(':guid'),
            'owner_guid' => new RawExp(':owner_guid'),
            'type' => 'audio',
            'subtype' => null,
            'access_id' => '0',
        ])->willReturn($insertMock);
        $insertMock->prepare()->willReturn($stmtMock);

        $stmtMock->execute([
            'guid' => 123,
            'owner_guid' => 456
        ])->shouldBeCalled()
            ->willReturn(true);

        $insertMock->into('minds_entities_audio')->willReturn($insertMock);
        $insertMock->set([
            'tenant_id' => -1,
            'guid' => new RawExp(':guid'),
            'created_at' => new RawExp(':created_at'),
        ])->willReturn($insertMock);
        $insertMock->prepare()->willReturn($stmtMock);

        $stmtMock->execute([
            'guid' => 123,
            'created_at' => date('c')
        ])->shouldBeCalled()
            ->willReturn(true);
        
        $this->mysqlMasterConnectionMock->commit()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->add($audioEntity)->shouldBe(true);
    }

    public function it_should_update_audio_entity(UpdateQuery $updateMock, PDOStatement $stmtMock)
    {
        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456,
            uploadedAt: new DateTimeImmutable('2024-11-08T09:02:57+00:00'),
            durationSecs: 12.5,
            processedAt: new DateTimeImmutable('2024-10-08T09:02:57+00:00'),
        );

        $this->mysqlClientWriterHandlerMock->update()->willReturn($updateMock);
        $updateMock->table('minds_entities_audio')->willReturn($updateMock);
        $updateMock->where('guid', Operator::EQ, new RawExp(':guid'))
            ->shouldBeCalled()
            ->willReturn($updateMock);
        $updateMock->where('tenant_id', Operator::EQ, -1)
            ->shouldBeCalled()
            ->willReturn($updateMock);
        $updateMock->set([
            'uploaded_at' => new RawExp(':uploadedAt'),
            'processed_at' => new RawExp(':processedAt'),
            'duration_secs' => new RawExp(':durationSecs')
        ])
            ->shouldBeCalled()
            ->willReturn($updateMock);

        $updateMock->prepare()->shouldBeCalled()
            ->willReturn($stmtMock);

        $stmtMock->execute([
            'guid' => 123,
            'uploadedAt' => '2024-11-08T09:02:57+00:00',
            'processedAt' => '2024-10-08T09:02:57+00:00',
            'durationSecs' => 12.5
        ])
            ->shouldBeCalled()
            ->willReturn(true);
            
        $this->update($audioEntity, [ 'uploadedAt', 'processedAt', 'durationSecs' ])
            ->shouldBe(true);
    }

    public function it_should_update_the_access_id(UpdateQuery $updateMock, PDOStatement $stmtMock)
    {
        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456,
            accessId: 789,
        );

        $this->mysqlClientWriterHandlerMock->update()->willReturn($updateMock);
        $updateMock->table('minds_entities')->willReturn($updateMock);
        $updateMock->where('guid', Operator::EQ, new RawExp(':guid'))
            ->shouldBeCalled()
            ->willReturn($updateMock);
        $updateMock->where('tenant_id', Operator::EQ, -1)
            ->shouldBeCalled()
            ->willReturn($updateMock);
        $updateMock->set([
            'access_id' => new RawExp(':accessId')
        ])
            ->shouldBeCalled()
            ->willReturn($updateMock);

        $updateMock->prepare()->willReturn($stmtMock);

        $stmtMock->execute([
            'guid' => 123,
            'accessId' => 789
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->updateAccessId($audioEntity)->shouldBe(true);
    }

    public function it_should_return_audio_entity_from_guid(SelectQuery $selectMock, PDOStatement $stmtMock)
    {
        $this->mysqlClientReaderHandlerMock->select()->willReturn($selectMock);
        $selectMock->from(new RawExp('minds_entities_audio as a'))->willReturn($selectMock);
        $selectMock->joinRaw(['e' => 'minds_entities'], 'e.tenant_id = a.tenant_id AND e.guid = a.guid')->willReturn($selectMock);
        $selectMock->where('e.tenant_id', '=', -1)->shouldBeCalled()->willReturn($selectMock);
        $selectMock->where('e.guid', '=', new RawExp(':guid'))->shouldBeCalled()->willReturn($selectMock);
        $selectMock->prepare()->willReturn($stmtMock);

        $stmtMock->execute([
            'guid' => 123
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $stmtMock->fetchAll(PDO::FETCH_ASSOC)->willReturn([
            [
                'guid' => 123,
                'owner_guid' => 456,
                'access_id' => 0,
                'duration_secs' => 12.5,
                'uploaded_at' => null,
            ]
        ]);

        $audioEntity = $this->getByGuid(123);
        $audioEntity->guid->shouldBe(123);
        $audioEntity->ownerGuid->shouldBe(456);
        $audioEntity->accessId->shouldBe(0);
        $audioEntity->durationSecs->shouldBe(12.5);
        $audioEntity->uploadedAt->shouldBe(null);
    }

    public function it_should_return_audio_entity_from_guid_with_uploaded_timestamp(SelectQuery $selectMock, PDOStatement $stmtMock)
    {
        $this->mysqlClientReaderHandlerMock->select()->willReturn($selectMock);
        $selectMock->from(new RawExp('minds_entities_audio as a'))->willReturn($selectMock);
        $selectMock->joinRaw(['e' => 'minds_entities'], 'e.tenant_id = a.tenant_id AND e.guid = a.guid')->willReturn($selectMock);
        $selectMock->where('e.tenant_id', '=', -1)->shouldBeCalled()->willReturn($selectMock);
        $selectMock->where('e.guid', '=', new RawExp(':guid'))->shouldBeCalled()->willReturn($selectMock);
        $selectMock->prepare()->willReturn($stmtMock);

        $stmtMock->execute([
            'guid' => 123
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $stmtMock->fetchAll(PDO::FETCH_ASSOC)->willReturn([
            [
                'guid' => 123,
                'owner_guid' => 456,
                'access_id' => 0,
                'duration_secs' => 12.5,
                'uploaded_at' => '2024-10-08T09:02:57+00:00',
            ]
        ]);

        $audioEntity = $this->getByGuid(123);
        $audioEntity->guid->shouldBe(123);
        $audioEntity->ownerGuid->shouldBe(456);
        $audioEntity->accessId->shouldBe(0);
        $audioEntity->durationSecs->shouldBe(12.5);
        $audioEntity->uploadedAt->format('c')->shouldBe('2024-10-08T09:02:57+00:00');
    }

    public function it_should_return_null_if_audio_not_found(SelectQuery $selectMock, PDOStatement $stmtMock)
    {
        $this->mysqlClientReaderHandlerMock->select()->willReturn($selectMock);
        $selectMock->from(new RawExp('minds_entities_audio as a'))->willReturn($selectMock);
        $selectMock->joinRaw(['e' => 'minds_entities'], 'e.tenant_id = a.tenant_id AND e.guid = a.guid')->willReturn($selectMock);
        $selectMock->where('e.tenant_id', '=', -1)->shouldBeCalled()->willReturn($selectMock);
        $selectMock->where('e.guid', '=', new RawExp(':guid'))->shouldBeCalled()->willReturn($selectMock);
        $selectMock->prepare()->willReturn($stmtMock);

        $stmtMock->execute([
            'guid' => 123
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $stmtMock->fetchAll(PDO::FETCH_ASSOC)->willReturn([]);

        $this->getByGuid(123)->shouldBe(null);
    }
}
