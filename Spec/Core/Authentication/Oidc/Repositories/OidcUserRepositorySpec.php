<?php

namespace Spec\Minds\Core\Authentication\Oidc\Repositories;

use Minds\Core\Authentication\Oidc\Repositories\OidcUserRepository;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class OidcUserRepositorySpec extends ObjectBehavior
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
        $this->shouldHaveType(OidcUserRepository::class);
    }

    public function it_should_get_user_guid_from_sub(PDOStatement $pdoStatementMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->execute(Argument::any())->willReturn(true);
        $pdoStatementMock->rowCount()->willReturn(1);
        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)->willReturn([
            [
                'user_guid' => 123
            ]
            ]);

        $this->getUserGuidFromSub('my-oidc-sub', 1)->shouldBe(123);
    }

    public function it_should_link_sub_to_user_guid(PDOStatement $pdoStatementMock)
    {
        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->execute(Argument::any())->willReturn(true);

        $this->linkSubToUserGuid('my-oidc-sub', 1, 123)->shouldBe(true);
    }

    public function it_should_return_object_for_matching_oidc_provider(PDOStatement $pdoStatementMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->execute(Argument::any())->willReturn(true);
        $pdoStatementMock->rowCount()->willReturn(1);
        $pdoStatementMock->fetch(PDO::FETCH_ASSOC)->willReturn(
            [
                'provider_id' => 1,
                'sub' => 'sub'
            ]
        );

        $obj = $this->getSubFromUserGuid(1);
        $obj->providerId->shouldBe(1);
        $obj->sub->shouldBe('sub');
    }

    public function it_should_not_return_object_for_matching_oidc_provider(PDOStatement $pdoStatementMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->execute(Argument::any())->willReturn(true);
        $pdoStatementMock->rowCount()->willReturn(0);
       
        $this->getSubFromUserGuid(1)->shouldBe(null);
    }

}
