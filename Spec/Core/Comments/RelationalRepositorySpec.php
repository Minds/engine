<?php

namespace Spec\Minds\Core\Comments;

use PDO;
use PDOStatement;
use Minds\Core\Comments\RelationalRepository;
use Minds\Core\Data\MySQL\Client as MySQLClient;

use Minds\Core\Comments\Comment;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;
use Selective\Database\Connection;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Selective\Database\InsertQuery;

use PhpSpec\Wrapper\Collaborator;

class RelationalRepositorySpec extends ObjectBehavior
{
    private Collaborator $mysqlClientMock;
    private Collaborator $mysqlMasterMock;
    private Collaborator $mysqlReplicaMock;

    public function let(
        MySQLClient $mysqlClient,
        PDO $mysqlMasterMock,
        PDO $mysqlReplicaMock,
        Connection $mysqlClientWriterHandlerMock,
    ) {
        $this->beConstructedWith($mysqlClient, Di::_()->get('Logger'));

        $this->mysqlClientMock = $mysqlClient;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::MASTER)
            ->willReturn($mysqlMasterMock);
        $this->mysqlMasterMock = $mysqlMasterMock;


        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::REPLICA)
            ->willReturn($mysqlReplicaMock);
        $this->mysqlReplicaMock = $mysqlReplicaMock;

    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(RelationalRepository::class);
    }

    public function it_should_add(
        Comment $comment,
        PDOStatement $statement,
    ) {
        // Comment
        $comment->getGuid()
            ->shouldBeCalled()
            ->willReturn('1000');
        $comment->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn('2000');
        $comment->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn('3000');
        $comment->getBody()
            ->shouldBeCalled()
            ->willReturn('Body');
        $comment->getAttachments()
            ->shouldBeCalled()
            ->willReturn([]);
        $comment->isMature()
            ->shouldBeCalled()
            ->willReturn(false);
        $comment->isEdited()
            ->shouldBeCalled()
            ->willReturn(false);
        $comment->isSpam()
            ->shouldBeCalled()
            ->willReturn(false);
        $comment->isDeleted()
            ->shouldBeCalled()
            ->willReturn(false);
        $comment->isGroupConversation()
            ->shouldBeCalled()
            ->willReturn(false);
        $comment->getAccessId()
            ->shouldBeCalled()
            ->willReturn('1000');
        $comment->getSource()
            ->shouldBeCalled()
            ->willReturn(FederatedEntitySourcesEnum::LOCAL);
        $comment->getCanonicalUrl()
            ->shouldBeCalled()
            ->willReturn(null);


        $this->mysqlMasterMock->prepare(Argument::type('string'))->shouldBeCalled()->willReturn($statement);
        $this->mysqlMasterMock->quote(Argument::type('string'))->willReturn('');
        $this->mysqlClientMock->bindValuesToPreparedStatement(Argument::type('object'), Argument::type('array'))
            ->shouldBeCalledOnce();

        $statement->execute()->shouldBeCalled()->willReturn(true);
        $statement->closeCursor()->shouldBeCalled()->willReturn(true);

        $this->add($comment, '2019-01-01 00:00:00', '2019-01-01 00:00:00', null, 0)->shouldReturn(true);
    }
}
