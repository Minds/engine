<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Admin\Repositories;

use Minds\Core\Admin\Repositories\HashtagExclusionRepository;
use Minds\Core\Admin\Types\HashtagExclusion\HashtagExclusionNode;
use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Log\Logger;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Selective\Database\Connection;
use Selective\Database\DeleteQuery;
use Selective\Database\InsertQuery;
use Selective\Database\SelectQuery;

class HashtagExclusionRepositorySpec extends ObjectBehavior
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


    public function it_is_initializable()
    {
        $this->shouldHaveType(HashtagExclusionRepository::class);
    }

    public function it_should_upsert_tag(
        InsertQuery $insertQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $tag = 'test';
        $adminGuid = 123;

        $this->mysqlClientWriterHandlerMock->insert()
            ->willReturn($insertQueryMock);
        $insertQueryMock->into('minds_admin_hashtag_exclusions')
            ->willReturn($insertQueryMock);
        $insertQueryMock->set(Argument::type('array'))
            ->willReturn($insertQueryMock);
        $insertQueryMock->onDuplicateKeyUpdate(Argument::type('array'))
            ->willReturn($insertQueryMock);
        $insertQueryMock->prepare()
            ->willReturn($pdoStatementMock);
        $pdoStatementMock->execute(Argument::type('array'))
            ->willReturn(true);

        $this->upsertTag($tag, $adminGuid)->shouldReturn(true);
    }

    public function it_should_throw_exception_when_upsert_tag_fails(
        InsertQuery $insertQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $tag = 'test';
        $adminGuid = 123;

        $this->mysqlClientWriterHandlerMock->insert()
            ->willReturn($insertQueryMock);
        $insertQueryMock->into('minds_admin_hashtag_exclusions')
            ->willReturn($insertQueryMock);
        $insertQueryMock->set(Argument::type('array'))
            ->willReturn($insertQueryMock);
        $insertQueryMock->onDuplicateKeyUpdate(Argument::type('array'))
            ->willReturn($insertQueryMock);
        $insertQueryMock->prepare()
            ->willReturn($pdoStatementMock);
        $pdoStatementMock->execute(Argument::type('array'))
            ->willThrow(new \PDOException());

        $this->shouldThrow(ServerErrorException::class)->during('upsertTag', [$tag, $adminGuid]);
    }

    public function it_should_remove_tag(
        DeleteQuery $deleteQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $tag = 'test';

        $this->mysqlClientWriterHandlerMock->delete()
            ->willReturn($deleteQueryMock);
        $deleteQueryMock->from('minds_admin_hashtag_exclusions')
            ->willReturn($deleteQueryMock);
        $deleteQueryMock->where(Argument::type('string'), Argument::type('string'), Argument::any())
            ->willReturn($deleteQueryMock);
        $deleteQueryMock->prepare()
            ->willReturn($pdoStatementMock);
        $pdoStatementMock->execute(Argument::type('array'))
            ->willReturn(true);

        $this->removeTag($tag)->shouldReturn(true);
    }

    public function it_should_throw_exception_when_remove_tag_fails(
        DeleteQuery $deleteQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $tag = 'test';

        $this->mysqlClientWriterHandlerMock->delete()
            ->willReturn($deleteQueryMock);
        $deleteQueryMock->from('minds_admin_hashtag_exclusions')
            ->willReturn($deleteQueryMock);
        $deleteQueryMock->where(Argument::type('string'), Argument::type('string'), Argument::any())
            ->willReturn($deleteQueryMock);
        $deleteQueryMock->prepare()
            ->willReturn($pdoStatementMock);
        $pdoStatementMock->execute(Argument::type('array'))
            ->willThrow(new \PDOException());

        $this->shouldThrow(ServerErrorException::class)->during('removeTag', [$tag]);
    }
}
