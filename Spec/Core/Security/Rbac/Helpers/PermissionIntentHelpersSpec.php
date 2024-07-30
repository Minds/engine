<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Security\Rbac\Helpers;

use Minds\Core\Security\Rbac\Enums\PermissionIntentTypeEnum;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Helpers\PermissionIntentHelpers;
use Minds\Core\Security\Rbac\Models\PermissionIntent;
use PhpSpec\ObjectBehavior;

class PermissionIntentHelpersSpec extends ObjectBehavior
{
    public function it_is_initializable(): void
    {
        $this->shouldHaveType(PermissionIntentHelpers::class);
    }

    // getTenantDefaultIntentType

    public function it_should_get_default_intent_type_for_can_create_post(): void
    {
        $permissionId = PermissionsEnum::CAN_CREATE_POST;
        $intentType = PermissionIntentTypeEnum::HIDE;

        $this->getTenantDefaultIntentType($permissionId)->shouldReturn($intentType);
    }

    public function it_should_get_default_intent_type_for_interact(): void
    {
        $permissionId = PermissionsEnum::CAN_INTERACT;
        $intentType = PermissionIntentTypeEnum::WARNING_MESSAGE;

        $this->getTenantDefaultIntentType($permissionId)->shouldReturn($intentType);
    }

    public function it_should_get_default_intent_type_for_upload_video(): void
    {
        $permissionId = PermissionsEnum::CAN_UPLOAD_VIDEO;
        $intentType = PermissionIntentTypeEnum::WARNING_MESSAGE;

        $this->getTenantDefaultIntentType($permissionId)->shouldReturn($intentType);
    }

    public function it_should_get_default_intent_type_for_create_chat_room(): void
    {
        $permissionId = PermissionsEnum::CAN_CREATE_CHAT_ROOM;
        $intentType = PermissionIntentTypeEnum::WARNING_MESSAGE;

        $this->getTenantDefaultIntentType($permissionId)->shouldReturn($intentType);
    }

    public function it_should_get_default_intent_type_for_comment(): void
    {
        $permissionId = PermissionsEnum::CAN_COMMENT;
        $intentType = PermissionIntentTypeEnum::WARNING_MESSAGE;

        $this->getTenantDefaultIntentType($permissionId)->shouldReturn($intentType);
    }

    public function it_should_get_default_intent_type_for_other(): void
    {
        $permissionId = PermissionsEnum::CAN_USE_RSS_SYNC;
        $intentType = PermissionIntentTypeEnum::WARNING_MESSAGE;

        $this->getTenantDefaultIntentType($permissionId)->shouldReturn($intentType);
    }


    // getNonTenantDefaults

    public function it_should_get_non_tenant_defaults(): void
    {
        $this->getNonTenantDefaults()
            ->shouldBeLike([
                new PermissionIntent(
                    permissionId: PermissionsEnum::CAN_CREATE_POST,
                    intentType: PermissionIntentTypeEnum::HIDE,
                    membershipGuid: null
                ),
                new PermissionIntent(
                    permissionId: PermissionsEnum::CAN_INTERACT,
                    intentType: PermissionIntentTypeEnum::WARNING_MESSAGE,
                    membershipGuid: null
                ),
                new PermissionIntent(
                    permissionId: PermissionsEnum::CAN_UPLOAD_VIDEO,
                    intentType: PermissionIntentTypeEnum::WARNING_MESSAGE,
                    membershipGuid: null
                ),
                new PermissionIntent(
                    permissionId: PermissionsEnum::CAN_CREATE_CHAT_ROOM,
                    intentType: PermissionIntentTypeEnum::WARNING_MESSAGE,
                    membershipGuid: null
                ),
                new PermissionIntent(
                    permissionId: PermissionsEnum::CAN_COMMENT,
                    intentType: PermissionIntentTypeEnum::WARNING_MESSAGE,
                    membershipGuid: null
                )
            ]);
    }
}
