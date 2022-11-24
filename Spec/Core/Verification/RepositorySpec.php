<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Verification;

use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Verification\Exceptions\VerificationRequestNotFoundException;
use Minds\Core\Verification\Models\VerificationRequest;
use Minds\Core\Verification\Repository;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    private Collaborator $mysqlHandler;
    private Collaborator $mysqlClientReader;
    private Collaborator $mysqlClientWriter;

    public function let(
        MySQLClient $mysqlHandler,
        PDO $mysqlClientReader,
        PDO $mysqlClientWriter
    ): void {
        $this->mysqlHandler = $mysqlHandler;

        $this->mysqlClientReader = $mysqlClientReader;
        $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_REPLICA)
            ->willReturn($this->mysqlClientReader);

        $this->mysqlClientWriter = $mysqlClientWriter;
        $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_MASTER)
            ->willReturn($this->mysqlClientWriter);

        $this->beConstructedWith($this->mysqlHandler);
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(Repository::class);
    }

    public function it_should_successfully_get_verification_request_details(
        PDOStatement $statement
    ): void {
        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $statement->rowCount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $statement->fetch(Argument::type('integer'))
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $this->mysqlClientReader->prepare(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->getVerificationRequestDetails('123', '123')
            ->shouldBeAnInstanceOf(VerificationRequest::class);
    }

    public function it_should_try_to_get_verification_request_details_and_throw_request_not_found_exception(
        PDOStatement $statement
    ): void {
        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $statement->rowCount()
            ->shouldBeCalledOnce()
            ->willReturn(0);

        $this->mysqlClientReader->prepare(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->shouldThrow(VerificationRequestNotFoundException::class)->during('getVerificationRequestDetails', ['123', '123']);
    }

    public function it_should_successfully_create_verification_request(
        PDOStatement $statement,
        VerificationRequest $verificationRequest
    ): void {
        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $verificationRequest->getUserGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $verificationRequest->getDeviceId()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $verificationRequest->getDeviceToken()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $verificationRequest->getStatus()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $verificationRequest->getVerificationCode()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $verificationRequest->getIpAddr()
            ->shouldBeCalledOnce()
            ->willReturn('');

        $this->mysqlClientWriter->prepare(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->createVerificationRequest($verificationRequest)
            ->shouldBeEqualTo(true);
    }

    public function it_should_successfully_update_verification_request_status(
        PDOStatement $statement,
        VerificationRequest $verificationRequest
    ): void {
        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $verificationRequest->getUserGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $verificationRequest->getDeviceId()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $verificationRequest->getCreatedAt()
            ->shouldBeCalledOnce()
            ->willReturn(time());

        $this->mysqlClientWriter->prepare(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->updateVerificationRequestStatus($verificationRequest, 1)
            ->shouldBeEqualTo(true);
    }

    public function it_should_successfully_mark_verification_request_as_verified(
        PDOStatement $statement,
        VerificationRequest $verificationRequest
    ): void {
        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $verificationRequest->getUserGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $verificationRequest->getDeviceId()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $verificationRequest->getCreatedAt()
            ->shouldBeCalledOnce()
            ->willReturn(time());

        $this->mysqlClientWriter->prepare(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->markRequestAsVerified($verificationRequest, '0,0', '')
            ->shouldBeEqualTo(true);
    }
}
