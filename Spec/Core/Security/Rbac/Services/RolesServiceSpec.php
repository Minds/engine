<?php

namespace Spec\Minds\Core\Security\Rbac\Services;

use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\Security\Audit\Services\AuditService;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Enums\RolesEnum;
use Minds\Core\Security\Rbac\Models\Role;
use Minds\Core\Security\Rbac\Repository;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class RolesServiceSpec extends ObjectBehavior
{
    private Collaborator $configMock;
    private Collaborator $repositoryMock;
    private Collaborator $entitiesBuilderMock;
    private Collaborator $auditServiceMock;

    public function let(
        Config $configMock,
        Repository $repositoryMock,
        EntitiesBuilder $entitiesBuilderMock,
        AuditService $auditServiceMock,
    ) {
        $this->beConstructedWith($configMock, $repositoryMock, $entitiesBuilderMock, $auditServiceMock);
        $this->configMock = $configMock;
        $this->repositoryMock = $repositoryMock;
        $this->entitiesBuilderMock = $entitiesBuilderMock;
        $this->auditServiceMock = $auditServiceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(RolesService::class);
    }

    public function it_should_return_all_roles_for_non_multi_tenant()
    {
        $this->repositoryMock->getRoles()->shouldNotBeCalled();

        $roles = $this->getAllRoles();
        $roles->shouldHaveCount(7);
    }

    public function it_should_return_all_roles_for_multi_tenant()
    {
        $this->configMock->get('tenant_id')
            ->willReturn(1);

        $this->repositoryMock->getRoles()
            ->willReturn([
                RolesEnum::DEFAULT->value => new Role(
                    RolesEnum::DEFAULT->value,
                    RolesEnum::DEFAULT->name,
                    [
                        PermissionsEnum::CAN_CREATE_POST,
                    ]
                ),
            ]);

        $roles = $this->getAllRoles();
        $roles->shouldHaveCount(5);

        $roles[RolesEnum::OWNER->value]->permissions->shouldBe([
            PermissionsEnum::CAN_CREATE_POST,
            PermissionsEnum::CAN_COMMENT,
            PermissionsEnum::CAN_CREATE_GROUP,
            PermissionsEnum::CAN_UPLOAD_VIDEO,
            PermissionsEnum::CAN_INTERACT,
            PermissionsEnum::CAN_BOOST,
            PermissionsEnum::CAN_USE_RSS_SYNC,
            PermissionsEnum::CAN_ASSIGN_PERMISSIONS,
            PermissionsEnum::CAN_MODERATE_CONTENT,
            PermissionsEnum::CAN_CREATE_PAYWALL,
            PermissionsEnum::CAN_CREATE_CHAT_ROOM,
            PermissionsEnum::CAN_UPLOAD_CHAT_MEDIA,
            PermissionsEnum::CAN_UPLOAD_AUDIO,
        ]);

        $roles[RolesEnum::DEFAULT->value]->permissions->shouldBe([
            PermissionsEnum::CAN_CREATE_POST,
        ]);
    }

    public function it_should_return_all_permissions()
    {
        $this->getAllPermissions()->shouldBe(PermissionsEnum::cases());
    }

    public function it_should_return_roles_for_multi_tenant_user(User $subjectUser)
    {
        $this->configMock->get('tenant_id')
            ->willReturn(1);

        $tenant = new Tenant(id: 1);
        $this->configMock->get('tenant')
            ->willReturn($tenant);

        $subjectUser->getGuid()->willReturn(1);

        $this->repositoryMock->getUserRoles(Argument::any())
            ->shouldBeCalled()
            ->willReturn([
                new Role(RolesEnum::ADMIN->value, RolesEnum::ADMIN->name, []),
                new Role(RolesEnum::DEFAULT->value, RolesEnum::DEFAULT->name, [])
            ]);

        $roles = $this->getRoles($subjectUser);
        $roles->shouldHaveCount(2);

        $roles[0]->id->shouldBe(RolesEnum::ADMIN->value);
        $roles[1]->id->shouldBe(RolesEnum::DEFAULT->value);
    }

    public function it_should_return_roles_for_non_multi_tenant_user()
    {
        $subjectUser = new User();

        $roles = $this->getRoles($subjectUser);
        $roles->shouldHaveCount(1);

        $roles[0]->id->shouldBe(RolesEnum::DEFAULT->value);
    }

    public function it_should_return_roles_for_non_multi_tenant_admin(User $subjectUser)
    {
        $subjectUser->isAdmin()->willReturn(true);
        $subjectUser->isPlus()->willReturn(false);
        $subjectUser->isPro()->willReturn(false);

        $roles = $this->getRoles($subjectUser);
        $roles->shouldHaveCount(2);

        $roles[0]->id->shouldBe(RolesEnum::ADMIN->value);
        $roles[1]->id->shouldBe(RolesEnum::DEFAULT->value);
    }

    public function it_should_return_roles_for_plus_user(User $subjectUser)
    {
        $subjectUser->isAdmin()->willReturn(false);
        $subjectUser->isPlus()->willReturn(true);
        $subjectUser->isPro()->willReturn(false);

        $roles = $this->getRoles($subjectUser);
        $roles->shouldHaveCount(2);


        $roles[0]->id->shouldBe(RolesEnum::PLUS->value);
        $roles[0]->permissions->shouldContain(PermissionsEnum::CAN_UPLOAD_AUDIO);
    }

    public function it_should_return_roles_for_pro_user(User $subjectUser)
    {
        $subjectUser->isAdmin()->willReturn(false);
        $subjectUser->isPlus()->willReturn(true);
        $subjectUser->isPro()->willReturn(true);

        $roles = $this->getRoles($subjectUser);
        $roles->shouldHaveCount(3);


        $roles[0]->id->shouldBe(RolesEnum::PLUS->value);
        $roles[0]->permissions->shouldContain(PermissionsEnum::CAN_UPLOAD_AUDIO);
    }

    public function it_should_return_user_permissions_for_multi_tenant_user()
    {
        $this->configMock->get('tenant_id')
            ->willReturn(1);

        $tenant = new Tenant(id: 1);
        $this->configMock->get('tenant')
            ->willReturn($tenant);

        $subjectUser = new User();

        $this->repositoryMock->getUserRoles(Argument::any())
            ->shouldBeCalled()
            ->willReturn([
                new Role(
                    RolesEnum::ADMIN->value,
                    RolesEnum::ADMIN->name,
                    [
                        PermissionsEnum::CAN_BOOST
                    ]
                ),
                new Role(
                    RolesEnum::DEFAULT->value,
                    RolesEnum::DEFAULT->name,
                    [
                        PermissionsEnum::CAN_CREATE_POST,
                    ]
                ),
            ]);

        $permissions = $this->getUserPermissions($subjectUser);
        $permissions->shouldHaveCount(2);
        $permissions->shouldBe([
            PermissionsEnum::CAN_BOOST,
            PermissionsEnum::CAN_CREATE_POST,
        ]);
    }

    public function it_should_return_resitricted_permissions_for_suspended_tenant()
    {
        $this->configMock->get('tenant_id')
            ->willReturn(1);

        $tenant = new Tenant(id: 1, suspendedTimestamp: time());
        $this->configMock->get('tenant')
            ->willReturn($tenant);

        $subjectUser = new User();

        $this->repositoryMock->getUserRoles(Argument::any())
            ->shouldNotBeCalled();
        
        $this->repositoryMock->getRoles()
            ->willReturn([
                RolesEnum::DEFAULT->value => new Role(
                    RolesEnum::DEFAULT->value,
                    RolesEnum::DEFAULT->name,
                    [
                        PermissionsEnum::CAN_CREATE_POST,
                    ]
                ),
            ]);

        $permissions = $this->getUserPermissions($subjectUser);
        $permissions->shouldHaveCount(0);
        $permissions->shouldBe([
        ]);
    }

    public function it_should_return_user_permissions_for_non_multi_tenant_user()
    {
        $subjectUser = new User();

        $permissions = $this->getUserPermissions($subjectUser);
        $permissions->shouldHaveCount(7);
        $permissions->shouldBe([
            PermissionsEnum::CAN_CREATE_POST,
            PermissionsEnum::CAN_COMMENT,
            PermissionsEnum::CAN_CREATE_GROUP,
            PermissionsEnum::CAN_UPLOAD_VIDEO,
            PermissionsEnum::CAN_INTERACT,
            PermissionsEnum::CAN_BOOST,
            PermissionsEnum::CAN_CREATE_CHAT_ROOM,
        ]);
    }

    public function it_should_return_true_if_a_user_has_permission()
    {
        $subjectUser = new User();
        $this->hasPermission($subjectUser, PermissionsEnum::CAN_CREATE_POST)->shouldBe(true);
    }

    public function it_should_return_false_if_a_user_doesnt_have_permission()
    {
        $subjectUser = new User();
        $this->hasPermission($subjectUser, PermissionsEnum::CAN_ASSIGN_PERMISSIONS)->shouldBe(false);
    }

    public function it_should_return_a_list_of_users()
    {
        $this->repositoryMock->getUsersByRole(null, null, 13, 0)
            ->shouldBeCalled()
            ->willYield([
                1 => [ RolesEnum::OWNER->value, RolesEnum::ADMIN->value ],
                2 => [  ]
            ]);

        $this->entitiesBuilderMock->single(Argument::any())
            ->willReturn(new User());

        $edges = $this->getUsersByRole();
        $edges->shouldHaveCount(2);
    }

    public function it_should_assign_a_user_to_role()
    {
        $this->configMock->get('tenant_id')
            ->willReturn(1);

        $adminUser = new User();
        $subjectUser = new User();

        $role = new Role(RolesEnum::ADMIN->value, RolesEnum::ADMIN->name, []);

        $this->repositoryMock->assignUserToRole(Argument::any(), RolesEnum::ADMIN->value)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->auditServiceMock->log('rbac_assign_role', Argument::any(), $adminUser)
            ->shouldBeCalled();

        $this->assignUserToRole($subjectUser, $role, $adminUser)->shouldBe(true);
    }

    public function it_should_unassign_a_user_from_role()
    {
        $this->configMock->get('tenant_id')
            ->willReturn(1);

        $subjectUser = new User();

        $role = new Role(RolesEnum::ADMIN->value, RolesEnum::ADMIN->name, []);

        $this->repositoryMock->unassignUserFromRole(Argument::any(), RolesEnum::ADMIN->value)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->auditServiceMock->log('rbac_unassign_role', Argument::any())
            ->shouldBeCalled();

        $this->unassignUserFromRole($subjectUser, $role)->shouldBe(true);
    }

    public function it_should_set_role_permissions()
    {
        $this->configMock->get('tenant_id')
            ->willReturn(1);

        $this->repositoryMock->setRolePermissions([
            PermissionsEnum::CAN_BOOST
        ], RolesEnum::ADMIN->value)->willReturn(true);


        $this->auditServiceMock->log('rbac_set_permissions', Argument::any())
            ->shouldBeCalled();

        $this->setRolePermissions([
            PermissionsEnum::CAN_BOOST
        ], new Role(RolesEnum::ADMIN->value, RolesEnum::ADMIN->name, []))->shouldBe(true);
    }
}
