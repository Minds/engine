<?php

namespace Spec\Minds\Core\Comments;

use PDO;
use PDOStatement;
use Minds\Core\Comments\RelationalRepository;
use Minds\Core\Data\MySQL\Client as MySQLClient;

use Minds\Core\Comments\Comment;
use Selective\Database\Connection;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Selective\Database\InsertQuery;

use PhpSpec\Wrapper\Collaborator;

class RelationalRepositorySpec extends ObjectBehavior
{
    private Collaborator $mysqlClient;
    private Collaborator $mysqlClientWriter;
    private Collaborator $mysqlClientWriterHandler;

    public function let(
        MySQLClient $mysqlClient,
        PDO    $mysqlClientWriter,
        Connection $mysqlClientWriterHandler,
    ) {
        $this->mysqlClient = $mysqlClient;

        $this->mysqlClientWriter = $mysqlClientWriter;
        $this->mysqlClient->getConnection(MySQLClient::CONNECTION_MASTER)
            ->willReturn($this->mysqlClientWriter);

        $mysqlClientWriterHandler->getPdo()->willReturn($this->mysqlClientWriter);
        $this->mysqlClientWriterHandler = $mysqlClientWriterHandler;

        $this->beConstructedWith($this->mysqlClient, $this->mysqlClientWriterHandler);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(RelationalRepository::class);
    }

    public function it_should_add(
        Comment $comment, 
        PDOStatement $statement, 
        InsertQuery $insertQuery
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

        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $insertQuery->into('minds_comments')
            ->shouldBeCalledOnce()
            ->willReturn($insertQuery);

        $insertQuery->set(Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn($insertQuery);

        $insertQuery->onDuplicateKeyUpdate(Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn($insertQuery);


        $this->mysqlClientWriterHandler->insert()
            ->shouldBeCalledOnce()
            ->willReturn($insertQuery);

        $insertQuery->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlClient->bindValuesToPreparedStatement(Argument::type('object'), Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->add($comment, '2019-01-01 00:00:00', '2019-01-01 00:00:00', null, 0)->shouldReturn(true);
    }
}