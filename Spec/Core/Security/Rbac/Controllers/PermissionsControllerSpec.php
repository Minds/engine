<?php

namespace Spec\Minds\Core\Security\Rbac\Controllers;

use GraphQL\Error\UserError;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\Rbac\Controllers\PermissionsController;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Enums\RolesEnum;
use Minds\Core\Security\Rbac\Models\Role;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Core\Security\Rbac\Types\UserRoleEdge;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class PermissionsControllerSpec extends ObjectBehavior
{
    private Collaborator $rolesServiceMock;
    private Collaborator $entitiesBuilderMock;

    public function let(RolesService $rolesServiceMock, EntitiesBuilder $entitiesBuilderMock)
    {
        $this->beConstructedWith($rolesServiceMock, $entitiesBuilderMock);
        $this->rolesServiceMock = $rolesServiceMock;
        $this->entitiesBuilderMock = $entitiesBuilderMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PermissionsController::class);
    }

    public function it_should_return_a_list_of_permissions()
    {
        $loggedInUser = new User();

        $this->rolesServiceMock->getUserPermissions($loggedInUser)
            ->willReturn(['CAN_CREATE_POST']);

        $this->getAssignedPermissions($loggedInUser)
            ->shouldBe([
                'CAN_CREATE_POST'
            ]);
    }

    public function it_should_return_a_list_of_assigned_roles()
    {
        $loggedInUser = new User();

        $this->rolesServiceMock->hasPermission($loggedInUser, PermissionsEnum::CAN_ASSIGN_PERMISSIONS)
            ->willReturn(true);

        $this->entitiesBuilderMock->single(1)
            ->willReturn($loggedInUser);

        $this->rolesServiceMock->getRoles($loggedInUser)
            ->willReturn([
                new Role(
                    id: RolesEnum::OWNER->value,
                    name: RolesEnum::OWNER->name,
                    permissions: [],
                ),
                new Role(
                    id: RolesEnum::DEFAULT->value,
                    name: RolesEnum::DEFAULT->name,
                    permissions: [],
                ),
            ]);

        $roles = $this->getAssignedRoles(1, $loggedInUser);
        $roles->shouldHaveCount(2);

        $roles[1]->name->shouldBe(RolesEnum::DEFAULT->name);

        $roles[0]->shouldBeAnInstanceOf(Role::class);
        $roles[1]->shouldBeAnInstanceOf(Role::class);
    }

    public function it_should_not_allow_non_permission_to_see_assigned_roles()
    {
        $loggedInUser = new User();

        $this->rolesServiceMock->hasPermission($loggedInUser, PermissionsEnum::CAN_ASSIGN_PERMISSIONS)
            ->willReturn(false);

        $this->shouldThrow(UserError::class)->duringGetAssignedRoles(1, $loggedInUser);
    }

    public function it_should_return_all_roles()
    {
        $this->rolesServiceMock->getAllRoles()->willReturn([
            new Role(
                id: RolesEnum::OWNER->value,
                name: RolesEnum::OWNER->name,
                permissions: [],
            ),
            new Role(
                id: RolesEnum::ADMIN->value,
                name: RolesEnum::ADMIN->name,
                permissions: [],
            ),
            new Role(
                id: RolesEnum::DEFAULT->value,
                name: RolesEnum::DEFAULT->name,
                permissions: [],
            ),
        ]);

        $roles = $this->getAllRoles();
        $roles->shouldHaveCount(3);

        $roles[0]->shouldBeAnInstanceOf(Role::class);
        $roles[1]->shouldBeAnInstanceOf(Role::class);
        $roles[2]->shouldBeAnInstanceOf(Role::class);
    }

    public function it_should_return_all_permisssions()
    {
        $this->rolesServiceMock->getAllPermissions()
            ->willReturn([
                'CAN_BOOST',
                'CAN_CREATE_POST',
                'CAN_CREATE_GROUP',
                'CAN_INTERACT',
            ]);

        $this->getAllPermissions()->shouldBe([
            'CAN_BOOST',
            'CAN_CREATE_POST',
            'CAN_CREATE_GROUP',
            'CAN_INTERACT',
        ]);
    }

    public function it_should_return_a_list_of_users_and_their_roles()
    {
        $loggedInUser = new User();

        $this->rolesServiceMock->hasPermission($loggedInUser, PermissionsEnum::CAN_ASSIGN_PERMISSIONS)
            ->willReturn(true);

        $this->rolesServiceMock->getUsersByRole(
            null,
            null,
            12,
            0,
            false
        )->willYield([
            new UserRoleEdge($loggedInUser, [
                new Role(
                    id: RolesEnum::OWNER->value,
                    name: RolesEnum::OWNER->name,
                    permissions: [],
                ),
            ]),
            new UserRoleEdge($loggedInUser, [
                new Role(
                    id: RolesEnum::DEFAULT->value,
                    name: RolesEnum::DEFAULT->name,
                    permissions: [],
                ),
            ]),
        ]);

        $connection = $this->getUsersByRole(loggedInUser: $loggedInUser);

        $edges = $connection->getEdges();

        $edges->shouldHaveCount(2);
        $edges[0]->shouldBeAnInstanceOf(UserRoleEdge::class);
        $edges[1]->shouldBeAnInstanceOf(UserRoleEdge::class);
    }

    public function it_should_assign_user_to_a_role()
    {
        $loggedInUser = new User();
        $subjectUser = new User();

        $this->rolesServiceMock->hasPermission($loggedInUser, PermissionsEnum::CAN_ASSIGN_PERMISSIONS)
            ->willReturn(true);

        $this->entitiesBuilderMock->single(1)->willReturn($subjectUser);

        $this->rolesServiceMock->getRoleById(RolesEnum::ADMIN->value)
            ->willReturn(new Role(RolesEnum::ADMIN->value, RolesEnum::ADMIN->name, []));

        $this->rolesServiceMock->assignUserToRole($subjectUser, Argument::type(Role::class))->shouldBeCalled()->willReturn(true);

        $this->assignUserToRole(1, RolesEnum::ADMIN->value, $loggedInUser);
    }

    public function it_should_not_allow_non_permission_to_assign_role()
    {
        $loggedInUser = new User();

        $this->rolesServiceMock->hasPermission($loggedInUser, PermissionsEnum::CAN_ASSIGN_PERMISSIONS)
            ->willReturn(false);

        $this->shouldThrow(UserError::class)->duringAssignUserToRole(1, RolesEnum::ADMIN->value, $loggedInUser);
    }

    public function it_should_unassign_user_from_a_role()
    {
        $loggedInUser = new User();
        $subjectUser = new User();

        $this->rolesServiceMock->hasPermission($loggedInUser, PermissionsEnum::CAN_ASSIGN_PERMISSIONS)
            ->willReturn(true);

        $this->entitiesBuilderMock->single(1)->willReturn($subjectUser);

        $this->rolesServiceMock->getRoleById(RolesEnum::ADMIN->value)
            ->willReturn(new Role(RolesEnum::ADMIN->value, RolesEnum::ADMIN->name, []));

        $this->rolesServiceMock->unassignUserFromRole($subjectUser, Argument::type(Role::class))->shouldBeCalled()->willReturn(true);

        $this->unassignUserFromRole(1, RolesEnum::ADMIN->value, $loggedInUser);
    }

    public function it_should_not_allow_non_permission_to_unassign_role()
    {
        $loggedInUser = new User();

        $this->rolesServiceMock->hasPermission($loggedInUser, PermissionsEnum::CAN_ASSIGN_PERMISSIONS)
            ->willReturn(false);

        $this->shouldThrow(UserError::class)->duringUnassignUserFromRole(1, RolesEnum::ADMIN->value, $loggedInUser);
    }

    public function it_should_set_role_permission()
    {
        $loggedInUser = new User();
        $subjectUser = new User();

        $this->rolesServiceMock->hasPermission($loggedInUser, PermissionsEnum::CAN_ASSIGN_PERMISSIONS)
            ->willReturn(true);

        $this->entitiesBuilderMock->single(1)->willReturn($subjectUser);

        $this->rolesServiceMock->getRoleById(RolesEnum::ADMIN->value)
            ->willReturn(new Role(RolesEnum::ADMIN->value, RolesEnum::ADMIN->name, [ PermissionsEnum::CAN_BOOST ]));

        $this->rolesServiceMock->setRolePermissions([
            PermissionsEnum::CAN_CREATE_POST->name => true,
        ], Argument::type(Role::class))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setRolePermission(PermissionsEnum::CAN_CREATE_POST, RolesEnum::ADMIN->value, true, $loggedInUser);
    }

    public function it_should_not_allow_non_permission_to_set_role_permission()
    {
        $loggedInUser = new User();

        $this->rolesServiceMock->hasPermission($loggedInUser, PermissionsEnum::CAN_ASSIGN_PERMISSIONS)
            ->willReturn(false);

        $this->shouldThrow(UserError::class)->duringSetRolePermission(PermissionsEnum::CAN_CREATE_POST, RolesEnum::ADMIN->value, true, $loggedInUser);
    }
}
