<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Security\Rbac\Controllers;

use Minds\Core\Guid;
use Minds\Core\Security\Rbac\Controllers\PermissionIntentsController;
use Minds\Core\Security\Rbac\Enums\PermissionIntentTypeEnum;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Models\PermissionIntent;
use Minds\Core\Security\Rbac\Services\PermissionIntentsService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class PermissionIntentsControllerSpec extends ObjectBehavior
{
    private Collaborator $permissionIntentsServiceMock;

    public function let(PermissionIntentsService $permissionIntentsServiceMock)
    {
        $this->beConstructedWith($permissionIntentsServiceMock);
        $this->permissionIntentsServiceMock = $permissionIntentsServiceMock;
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(PermissionIntentsController::class);
    }

    public function it_should_get_permission_intents(PermissionIntent $permissionIntent1, PermissionIntent $permissionIntent2): void
    {
        $permissionIntents = [ $permissionIntent1, $permissionIntent2 ];

        $this->permissionIntentsServiceMock->getPermissionIntents()
            ->willReturn($permissionIntents);

        $this->getPermissionIntents()
            ->shouldBe($permissionIntents);
    }

    public function it_should_set_permission_intent_for_non_upgrade_intent(): void
    {
        $permissionId = PermissionsEnum::CAN_COMMENT;
        $intentType = PermissionIntentTypeEnum::HIDE;

        $this->permissionIntentsServiceMock->setPermissionIntent(
            permissionId: $permissionId,
            intentType: $intentType,
            membershipGuid: null
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setPermissionIntent($permissionId, $intentType)
            ->shouldBeLike(new PermissionIntent(
                permissionId: $permissionId,
                intentType: $intentType,
                membershipGuid: null
            ));
    }

    public function it_should_set_permission_intent_for_upgrade_intent(): void
    {
        $permissionId = PermissionsEnum::CAN_COMMENT;
        $intentType = PermissionIntentTypeEnum::UPGRADE;
        $membershipGuid = (string) Guid::build();

        $this->permissionIntentsServiceMock->setPermissionIntent(
            permissionId: $permissionId,
            intentType: $intentType,
            membershipGuid: $membershipGuid
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setPermissionIntent($permissionId, $intentType, $membershipGuid)
            ->shouldBeLike(new PermissionIntent(
                permissionId: $permissionId,
                intentType: $intentType,
                membershipGuid: (int) $membershipGuid
            ));
    }
}
