<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\MobileConfigs\Delegates;

use Minds\Core\Email\V2\Campaigns\Recurring\MobileAppPreviewReady\MobileAppPreviewReadyEmailer;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\MobileConfigs\Delegates\MobileAppPreviewReadyEmailDelegate;
use Minds\Core\Security\Rbac\Enums\RolesEnum;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Core\Security\Rbac\Types\UserRoleEdge;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class MobileAppPreviewReadyEmailDelegateSpec extends ObjectBehavior
{
    private Collaborator $mobileAppPreviewReadyEmailerMock;
    private Collaborator $rolesServiceMock;
    private Collaborator $loggerMock;

    public function let(
        MobileAppPreviewReadyEmailer $mobileAppPreviewReadyEmailer,
        RolesService $rolesService,
        Logger $logger
    ) {
        $this->mobileAppPreviewReadyEmailerMock = $mobileAppPreviewReadyEmailer;
        $this->rolesServiceMock = $rolesService;
        $this->loggerMock = $logger;

        $this->beConstructedWith(
            $this->mobileAppPreviewReadyEmailerMock,
            $this->rolesServiceMock,
            $this->loggerMock
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(MobileAppPreviewReadyEmailDelegate::class);
    }

    public function it_should_send_email_to_all_admins_when_preview_is_ready(
        UserRoleEdge $userRoleEdge1,
        UserRoleEdge $userRoleEdge2,
        User $user1,
        User $user2
    ) {
        $userRoleEdge1->getUser()->willReturn($user1);
        $userRoleEdge2->getUser()->willReturn($user2);

        $this->rolesServiceMock->getUsersByRole(
            RolesEnum::OWNER->value,
            null,
            12,
            null,
            null
        )
            ->shouldBeCalled()
            ->willYield([$userRoleEdge1]);

        $this->rolesServiceMock->getUsersByRole(
            RolesEnum::ADMIN->value,
            null,
            12,
            null,
            null
        )
            ->shouldBeCalled()
            ->willYield([$userRoleEdge2]);
        
        $this->mobileAppPreviewReadyEmailerMock->setUser(Argument::any())
            ->shouldBeCalledTimes(2)
            ->willReturn($this->mobileAppPreviewReadyEmailerMock);

        $this->mobileAppPreviewReadyEmailerMock->queue()
            ->shouldBeCalledTimes(2);

        $this->onMobileAppPreviewReady(true);
    }

    public function it_should_send_email_when_preview_is_ready_to_all_admins_deduplicated(
        UserRoleEdge $userRoleEdge1,
        UserRoleEdge $userRoleEdge2,
        User $user1,
        User $user2
    ) {
        $userRoleEdge1->getUser()->willReturn($user1);
        $userRoleEdge2->getUser()->willReturn($user2);
  
        $this->rolesServiceMock->getUsersByRole(
            RolesEnum::OWNER->value,
            null,
            12,
            null,
            null
        )
            ->shouldBeCalled()
            ->willYield([$userRoleEdge1]);
  
        $this->rolesServiceMock->getUsersByRole(
            RolesEnum::ADMIN->value,
            null,
            12,
            null,
            null
        )
            ->shouldBeCalled()
            ->willYield([$userRoleEdge1, $userRoleEdge2]);
          
        $this->mobileAppPreviewReadyEmailerMock->setUser(Argument::any())
            ->shouldBeCalledTimes(2)
            ->willReturn($this->mobileAppPreviewReadyEmailerMock);
  
        $this->mobileAppPreviewReadyEmailerMock->queue()
            ->shouldBeCalledTimes(2);
  
        $this->onMobileAppPreviewReady(true);
    }
}
