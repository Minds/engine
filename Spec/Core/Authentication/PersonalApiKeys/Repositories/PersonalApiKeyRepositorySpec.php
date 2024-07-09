<?php

namespace Spec\Minds\Core\Authentication\PersonalApiKeys\Repositories;

use DateTimeImmutable;
use Minds\Core\Authentication\PersonalApiKeys\PersonalApiKey;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Authentication\PersonalApiKeys\Repositories\PersonalApiKeyRepository;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Router\Enums\ApiScopeEnum;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class PersonalApiKeyRepositorySpec extends ObjectBehavior
{
    private Collaborator $mysqlClientMock;
    private Collaborator $mysqlMasterMock;
    private Collaborator $mysqlReplicaMock;

    public function let(MySQLClient $mysqlClient, PDO $mysqlMasterMock, PDO $mysqlReplicaMock)
    {
        $this->beConstructedWith($mysqlClient, Di::_()->get(Config::class), Di::_()->get('Logger'));
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
        $this->shouldHaveType(PersonalApiKeyRepository::class);
    }

    public function it_should_add_to_the_database(PersonalApiKey $personalApiKey, PDOStatement $stmtMock)
    {
        $this->mysqlMasterMock->inTransaction()
            ->willReturn(false);
        $this->mysqlMasterMock->beginTransaction()
            ->shouldBeCalled();
        $this->mysqlMasterMock->quote(Argument::any())
            ->shouldBeCalled()
            ->willReturn(true);

        $this->mysqlMasterMock->prepare(Argument::any())
            ->willReturn($stmtMock);

        $personalApiKey = new PersonalApiKey(
            id: 'id',
            ownerGuid: 123,
            secretHash: 'hash',
            name: 'name',
            scopes: [
                ApiScopeEnum::ALL,
                ApiScopeEnum::SITE_MEMBERSHIP_WRITE,
            ],
            timeCreated: new DateTimeImmutable('midnight'),
        );

        $stmtMock->execute([
            'id' => 'id',
            'owner_guid' => 123,
            'secret_hash' => 'hash',
            'name' => 'name',
            'created_timestamp' => (new DateTimeImmutable('midnight'))->format('c'),
            'expires_timestamp' => null
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $stmtMock->execute([
            'id' => 'id',
            'scope' => 'ALL'
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $stmtMock->execute([
            'id' => 'id',
            'scope' => 'SITE_MEMBERSHIP_WRITE'
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->mysqlMasterMock->commit()
            ->shouldBeCalled();

        $this->add($personalApiKey)->shouldBe(true);
    }
}
