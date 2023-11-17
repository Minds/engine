<?php

namespace Spec\Minds\Core\Security\Rbac;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Security\Rbac\Repository;
use PhpSpec\ObjectBehavior;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Di\Di;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Enums\RolesEnum;
use PDO;
use PDOStatement;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    private Collaborator $multiTenantBootServiceMock;
    private Collaborator $mysqlClientMock;
    private Collaborator $mysqlMasterMock;
    private Collaborator $mysqlReplicaMock;

    public function let(
        Config $configMock,
        MultiTenantBootService $multiTenantBootServiceMock,
        MySQLClient $mysqlClientMock,
        PDO $mysqlMasterMock,
        PDO $mysqlReplicaMock
    ) {
        $this->beConstructedWith($configMock, $multiTenantBootServiceMock, $mysqlClientMock, Di::_()->get('Logger'));

        $this->multiTenantBootServiceMock = $multiTenantBootServiceMock;

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
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_return_all_roles(PDOStatement $stmtMock)
    {
        $this->mysqlReplicaMock->quote(Argument::any())->willReturn("");
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($stmtMock);

        $stmtMock->execute(Argument::any())->willReturn(true);

        $stmtMock->rowCount()->willReturn(2);

        $stmtMock->fetchAll(PDO::FETCH_ASSOC)->willReturn([
            [
                'role_id' => 0,
                'permissions' => "CAN_BOOST,CAN_CREATE_POST"
            ],
            [
                'role_id' => 4,
                'permissions' => "CAN_CREATE_POST"
            ]
        ]);

        $roles = $this->getRoles();
        $roles->shouldHaveCount(2);

        $roles[RolesEnum::OWNER->value]->permissions->shouldBe([
            PermissionsEnum::CAN_BOOST,
            PermissionsEnum::CAN_CREATE_POST,
        ]);

        $roles[RolesEnum::DEFAULT->value]->permissions->shouldBe([
            PermissionsEnum::CAN_CREATE_POST,
        ]);
    }

    public function it_should_get_user_roles(PDOStatement $stmtMock)
    {
        $this->mysqlReplicaMock->quote(Argument::any())->willReturn("");
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($stmtMock);

        $stmtMock->execute(Argument::any())->willReturn(true);

        $stmtMock->rowCount()->willReturn(2);

        $stmtMock->fetchAll(PDO::FETCH_ASSOC)->willReturn([
            [
                'role_id' => 0,
                'permissions' => "CAN_BOOST,CAN_CREATE_POST"
            ],
            [
                'role_id' => 4,
                'permissions' => "CAN_CREATE_POST"
            ]
        ]);

        $this->multiTenantBootServiceMock->getTenant()
            ->willReturn(new Tenant(id: 1, rootUserGuid: 1));

        $roles = $this->getUserRoles(1);
        $roles->shouldHaveCount(2);

        $roles[RolesEnum::OWNER->value]->permissions->shouldBe([
            PermissionsEnum::CAN_BOOST,
            PermissionsEnum::CAN_CREATE_POST,
        ]);

        $roles[RolesEnum::DEFAULT->value]->permissions->shouldBe([
            PermissionsEnum::CAN_CREATE_POST,
        ]);
    }

    // public function it_should_return_a_list_of_users(PDOStatement $stmtMock)
    // {
    //     $this->mysqlReplicaMock->quote(Argument::any())->willReturn("");
    //     $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($stmtMock);

    //     $stmtMock->execute(Argument::any())->willReturn(true);

    //     $stmtMock->fetchAll(PDO::FETCH_ASSOC)->willReturn([
    //         [
    //             'user_guid' => 1,
    //             'role_ids' => "0,4",
    //         ],
    //         [
    //             'user_guid' => 2,
    //             'role_ids' => "2",
    //         ]
    //     ]);

    //     $roles = $this->getUsersByRole(1);
    //     $roles->shouldHaveCount(2);

    //     $roles[RolesEnum::OWNER->value]->permissions->shouldBe([
    //         PermissionsEnum::CAN_BOOST,
    //         PermissionsEnum::CAN_CREATE_POST,
    //     ]);

    //     $roles[RolesEnum::DEFAULT->value]->permissions->shouldBe([
    //         PermissionsEnum::CAN_CREATE_POST,
    //     ]);
    // }

    // public function it_should_assign_user_to_a_role()
    // {

    // }

    // public function it_should_unassign_user_to_a_role()
    // {
        
    // }

    // public function it_should_set_role_permissions()
    // {

    // }
}
