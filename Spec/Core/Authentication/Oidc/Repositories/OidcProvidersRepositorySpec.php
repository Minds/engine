<?php

namespace Spec\Minds\Core\Authentication\Oidc\Repositories;

use Minds\Core\Authentication\Oidc\Models\OidcProvider;
use Minds\Core\Authentication\Oidc\Repositories\OidcProvidersRepository;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class OidcProvidersRepositorySpec extends ObjectBehavior
{
    private Collaborator $multiTenantBootServiceMock;
    private Collaborator $mysqlClientMock;
    private Collaborator $mysqlMasterMock;
    private Collaborator $mysqlReplicaMock;

    public function let(
        Config $configMock,
        MySQLClient $mysqlClientMock,
        PDO $mysqlMasterMock,
        PDO $mysqlReplicaMock
    ) {
        $this->beConstructedWith($mysqlClientMock, $configMock, Di::_()->get('Logger'));

        
        $this->mysqlClientMock = $mysqlClientMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::MASTER)
            ->willReturn($mysqlMasterMock);
        $this->mysqlMasterMock = $mysqlMasterMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::REPLICA)
            ->willReturn($mysqlReplicaMock);
        $this->mysqlReplicaMock = $mysqlReplicaMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(OidcProvidersRepository::class);
    }

    public function it_should_return_list_of_providers(PDOStatement $pdoStatementMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->execute(Argument::any())->willReturn(true);

        $pdoStatementMock->rowCount()->willReturn(2);

        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)->willReturn([
            [
                'provider_id' => 1,
                'name' => 'PHP Spec',
                'issuer' => 'https://phpspec.local',
                'client_id' => 'phpspec',
                'client_secret' => 'secrets',
            ],
            [
                'provider_id' => 2,
                'name' => null,
                'issuer' => 'https://phpspec.local',
                'client_id' => 'phpspec',
                'client_secret' => 'secrets',
            ]
        ]);

        $result = $this->getProviders();
        $result->shouldHaveCount(2);

        $result[0]->shouldBeAnInstanceOf(OidcProvider::class);
        $result[0]->id->shouldBe(1);
        $result[0]->name->shouldBe('PHP Spec');
        $result[0]->issuer->shouldBe('https://phpspec.local');
        $result[0]->clientId->shouldBe('phpspec');
        $result[0]->clientSecret->shouldBe('secrets');

        $result[1]->shouldBeAnInstanceOf(OidcProvider::class);
        $result[1]->id->shouldBe(2);
        $result[1]->name->shouldBe('Oidc');
        $result[1]->issuer->shouldBe('https://phpspec.local');
        $result[1]->clientId->shouldBe('phpspec');
        $result[1]->clientSecret->shouldBe('secrets');
    }
}
