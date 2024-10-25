<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\Config\Config;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRepository;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipOnlyModeService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsService;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Core\Log\Logger;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Enums\RolesEnum;
use Minds\Core\Security\Rbac\Models\Role;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class SiteMembershipOnlyModeServiceSpec extends ObjectBehavior
{
    private Collaborator $siteMembershipSubscriptionsServiceMock;
    private Collaborator $rolesServiceMock;
    private Collaborator $configMock;
    private Collaborator $loggerMock;

    public function let(
        SiteMembershipSubscriptionsService $siteMembershipSubscriptionsServiceMock,
        RolesService $rolesServiceMock,
        Config $configMock,
        Logger $loggerMock
    ): void {
        $this->siteMembershipSubscriptionsServiceMock = $siteMembershipSubscriptionsServiceMock;
        $this->rolesServiceMock = $rolesServiceMock;
        $this->configMock = $configMock;
        $this->loggerMock = $loggerMock;

        $this->beConstructedWith(
            $siteMembershipSubscriptionsServiceMock,
            $rolesServiceMock,
            $configMock,
            $loggerMock
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(SiteMembershipOnlyModeService::class);
    }

    public function it_should_not_restrict_access_when_members_only_mode_is_disabled(User $user): void
    {
        $this->configMock->get('tenant')->willReturn((object)[
            'config' => (object)[
                'membersOnlyModeEnabled' => false
            ]
        ]);

        $this->callOnWrappedObject('shouldRestrictAccess', [$user])->shouldReturn(false);
    }

    public function it_should_restrict_access_for_null_user_when_members_only_mode_is_enabled(): void
    {
        $this->configMock->get('tenant')->willReturn((object)[
            'config' => (object)[
                'membersOnlyModeEnabled' => true
            ]
        ]);

        $this->callOnWrappedObject('shouldRestrictAccess', [null])->shouldReturn(true);
    }

    public function it_should_not_restrict_access_for_admin_user(User $user): void
    {
        $this->configMock->get('tenant')->willReturn((object)[
            'config' => (object)[
                'membersOnlyModeEnabled' => true
            ]
        ]);

        $user->isAdmin()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->callOnWrappedObject('shouldRestrictAccess', [$user])->shouldReturn(false);
    }

    public function it_should_not_restrict_access_for_user_with_moderation_permission(User $user): void
    {
        $this->configMock->get('tenant')->willReturn((object)[
            'config' => (object)[
                'membersOnlyModeEnabled' => true
            ]
        ]);

        $this->rolesServiceMock->hasPermission($user, PermissionsEnum::CAN_MODERATE_CONTENT)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->callOnWrappedObject('shouldRestrictAccess', [$user])->shouldReturn(false);
    }

    public function it_should_not_restrict_access_for_user_with_active_membership_subscription_count_on_user(User $user): void
    {
        $this->configMock->get('tenant')->willReturn((object)[
            'config' => (object)[
                'membersOnlyModeEnabled' => true
            ]
        ]);

        $user->get('membership_subscriptions_count')
            ->shouldBeCalled()
            ->willReturn(1);

        $user->isAdmin()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->rolesServiceMock->hasPermission($user, PermissionsEnum::CAN_MODERATE_CONTENT)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->callOnWrappedObject('shouldRestrictAccess', [$user])->shouldReturn(false);
    }

    public function it_should_not_restrict_access_for_user_with_active_membership_subscription_in_cache(User $user): void
    {
        $this->configMock->get('tenant')->willReturn((object)[
            'config' => (object)[
                'membersOnlyModeEnabled' => true
            ]
        ]);

        $this->rolesServiceMock->hasPermission($user, PermissionsEnum::CAN_MODERATE_CONTENT)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->siteMembershipSubscriptionsServiceMock
            ->hasActiveSiteMembershipSubscription($user)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->callOnWrappedObject('shouldRestrictAccess', [$user])->shouldReturn(false);
    }

    public function it_should_restrict_access_for_user_without_active_membership_subscription(User $user): void
    {
        $this->configMock->get('tenant')->willReturn((object)[
            'config' => (object)[
                'membersOnlyModeEnabled' => true
            ]
        ]);

        $this->rolesServiceMock->hasPermission($user, PermissionsEnum::CAN_MODERATE_CONTENT)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->siteMembershipSubscriptionsServiceMock
            ->hasActiveSiteMembershipSubscription($user)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->callOnWrappedObject('shouldRestrictAccess', [$user])->shouldReturn(true);
    }

    public function it_should_not_restrict_access_when_exception_is_thrown(User $user): void
    {
        $this->configMock->get('tenant')->willReturn((object)[
            'config' => (object)[
                'membersOnlyModeEnabled' => true
            ]
        ]);

        $this->rolesServiceMock->hasPermission($user, PermissionsEnum::CAN_MODERATE_CONTENT)
            ->shouldBeCalled()
            ->willThrow(new \Exception('Error'));

        $this->loggerMock->error(Argument::type('string'))->shouldBeCalled();

        $this->callOnWrappedObject('shouldRestrictAccess', [$user])->shouldReturn(false);
    }
}
