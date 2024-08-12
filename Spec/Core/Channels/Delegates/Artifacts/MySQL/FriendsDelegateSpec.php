<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Channels\Delegates\Artifacts\MySQL;

use Minds\Core\Channels\Delegates\Artifacts\MySQL\FriendsDelegate;
use Minds\Core\Config;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Selective\Database\Connection;
use Selective\Database\DeleteQuery;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class FriendsDelegateSpec extends ObjectBehavior
{
    private Collaborator $mysqlHandlerMock;
    private Collaborator $mysqlClientReaderMock;
    private Collaborator $mysqlClientWriterMock;
    private Collaborator $mysqlClientReaderHandlerMock;
    private Collaborator $mysqlClientWriterHandlerMock;
    private Collaborator $configMock;

    public function let(
        Client $mysqlHandlerMock,
        PDO $mysqlClientReaderMock,
        PDO $mysqlClientWriterMock,
        Connection $mysqlClientReaderHandlerMock,
        Connection $mysqlClientWriterHandlerMock,
        Logger $loggerMock,
        Config $configMock
    ): void {
        $this->mysqlHandlerMock = $mysqlHandlerMock;

        $this->mysqlHandlerMock->getConnection(Client::CONNECTION_REPLICA)
            ->willReturn($this->mysqlClientReaderMock = $mysqlClientReaderMock);
        $this->mysqlHandlerMock->getConnection(Client::CONNECTION_MASTER)
            ->willReturn($this->mysqlClientWriterMock = $mysqlClientWriterMock);

        $mysqlClientReaderHandlerMock->getPdo()->willReturn($this->mysqlClientReaderMock);
        $this->mysqlClientReaderHandlerMock = $mysqlClientReaderHandlerMock;

        $mysqlClientReaderHandlerMock->getPdo()->willReturn($this->mysqlClientWriterMock);
        $this->mysqlClientWriterHandlerMock = $mysqlClientWriterHandlerMock;

        $this->configMock = $configMock;

        $this->beConstructedWith(
            $this->mysqlHandlerMock,
            $configMock,
            $loggerMock,
            $this->mysqlClientReaderHandlerMock,
            $this->mysqlClientWriterHandlerMock
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(FriendsDelegate::class);
    }

    public function it_should_delete(
        DeleteQuery $deleteQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $userGuid = Guid::build();
        $tenantId = 123;

        $this->configMock->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn($tenantId);

        $this->mysqlClientWriterHandlerMock->delete()
            ->shouldBeCalled()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->from('friends')
            ->shouldBeCalled()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->shouldBeCalled()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->where(Argument::type('callable'))
            ->shouldBeCalled()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->prepare()
            ->shouldBeCalled()
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            'tenant_id' => $tenantId,
            'user_guid' => $userGuid,
            'friend_guid' => $userGuid
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->delete($userGuid);
    }
}
