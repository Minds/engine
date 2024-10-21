<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\Config\Config;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRepository;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipOnlyModeService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsService;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Core\Log\Logger;
use Minds\Core\Security\Rbac\Enums\RolesEnum;
use Minds\Core\Security\Rbac\Models\Role;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class SiteMembershipOnlyModeServiceSpec extends ObjectBehavior
{
    private Collaborator $siteMembershipRepositoryMock;
    private Collaborator $siteMembershipSubscriptionsServiceMock;
    private Collaborator $rolesServiceMock;
    private Collaborator $configMock;
    private Collaborator $loggerMock;

    public function let(
        SiteMembershipRepository $siteMembershipRepositoryMock,
        SiteMembershipSubscriptionsService $siteMembershipSubscriptionsServiceMock,
        RolesService $rolesServiceMock,
        Config $configMock,
        Logger $loggerMock
    ): void {
        $this->siteMembershipRepositoryMock = $siteMembershipRepositoryMock;
        $this->siteMembershipSubscriptionsServiceMock = $siteMembershipSubscriptionsServiceMock;
        $this->rolesServiceMock = $rolesServiceMock;
        $this->configMock = $configMock;
        $this->loggerMock = $loggerMock;

        $this->beConstructedWith(
            $siteMembershipRepositoryMock,
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

    public function it_should_not_restrict_access_when_user_is_null(): void
    {
        $this->configMock->get('tenant')->willReturn(
            (object) ['config' => (object) ['membersOnlyModeEnabled' => true]]
        );
        $this->callOnWrappedObject('shouldRestrictAccess', [])->shouldBe(false);
    }

    public function it_should_not_restrict_access_when_tenant_config_is_null(): void
    {
        $this->configMock->get('tenant')->willReturn(null);
        $this->callOnWrappedObject('shouldRestrictAccess', [new User()])->shouldReturn(false);
    }

    public function it_should_not_restrict_access_when_members_only_mode_is_disabled(): void
    {
        $this->configMock->get('tenant')->willReturn((object) ['config' => (object) ['membersOnlyModeEnabled' => false]]);
        $this->callOnWrappedObject('shouldRestrictAccess', [new User()])->shouldReturn(false);
    }

    public function it_should_not_restrict_access_when_no_active_memberships(User $user): void
    {
        $this->configMock->get('tenant')->willReturn((object) ['config' => (object) ['membersOnlyModeEnabled' => true]]);
        $this->siteMembershipRepositoryMock->getTotalSiteMemberships()->willReturn(0);
        $this->callOnWrappedObject('shouldRestrictAccess', [$user])->shouldReturn(false);
    }

    public function it_should_not_restrict_access_for_admin_user(User $user): void
    {
        $this->configMock->get('tenant')->willReturn((object) ['config' => (object) ['membersOnlyModeEnabled' => true]]);
        $this->siteMembershipRepositoryMock->getTotalSiteMemberships()->willReturn(1);
        $user->isAdmin()->willReturn(true);
        $this->callOnWrappedObject('shouldRestrictAccess', [$user])->shouldReturn(false);
    }

    public function it_should_not_restrict_access_for_user_with_active_subscription(User $user): void
    {
        $this->configMock->get('tenant')->willReturn((object) ['config' => (object) ['membersOnlyModeEnabled' => true]]);
        $this->siteMembershipRepositoryMock->getTotalSiteMemberships()->willReturn(1);
        $user->isAdmin()->willReturn(false);
        $this->rolesServiceMock->getRoles($user)->willReturn([]);
        $this->siteMembershipSubscriptionsServiceMock->hasActiveSiteMembershipSubscription($user)->willReturn(true);
        $this->callOnWrappedObject('shouldRestrictAccess', [$user])->shouldReturn(false);
    }

    public function it_should_not_restrict_access_for_a_moderator(User $user): void
    {
        $this->configMock->get('tenant')->willReturn((object) ['config' => (object) ['membersOnlyModeEnabled' => true]]);
        $this->siteMembershipRepositoryMock->getTotalSiteMemberships()->willReturn(1);
        $user->isAdmin()->willReturn(false);

        $this->rolesServiceMock->getRoles($user)->willReturn([new Role(
            RolesEnum::MODERATOR->value,
            RolesEnum::MODERATOR->name,
            []
        )]);
        
        $this->callOnWrappedObject('shouldRestrictAccess', [$user])->shouldReturn(false);
    }

    public function it_should_not_restrict_access_for_an_owner(User $user): void
    {
        $this->configMock->get('tenant')->willReturn((object) ['config' => (object) ['membersOnlyModeEnabled' => true]]);
        $this->siteMembershipRepositoryMock->getTotalSiteMemberships()->willReturn(1);
        $user->isAdmin()->willReturn(false);

        $this->rolesServiceMock->getRoles($user)->willReturn([new Role(
            RolesEnum::OWNER->value,
            RolesEnum::OWNER->name,
            []
        )]);
        
        $this->callOnWrappedObject('shouldRestrictAccess', [$user])->shouldReturn(false);
    }

    public function it_should_not_restrict_access_for_an_admin_role(User $user): void
    {
        $this->configMock->get('tenant')->willReturn((object) ['config' => (object) ['membersOnlyModeEnabled' => true]]);
        $this->siteMembershipRepositoryMock->getTotalSiteMemberships()->willReturn(1);
        $user->isAdmin()->willReturn(false);

        $this->rolesServiceMock->getRoles($user)->willReturn([new Role(
            RolesEnum::ADMIN->value,
            RolesEnum::ADMIN->name,
            []
        )]);
        
        $this->callOnWrappedObject('shouldRestrictAccess', [$user])->shouldReturn(false);
    }

    public function it_should_restrict_access_for_regular_user_without_subscription(User $user): void
    {
        $this->configMock->get('tenant')->willReturn((object) ['config' => (object) ['membersOnlyModeEnabled' => true]]);
        $this->siteMembershipRepositoryMock->getTotalSiteMemberships()->willReturn(1);
        $user->isAdmin()->willReturn(false);
        $this->rolesServiceMock->getRoles($user)->willReturn([]);
        $this->siteMembershipSubscriptionsServiceMock->hasActiveSiteMembershipSubscription($user)->willReturn(false);
        $this->callOnWrappedObject('shouldRestrictAccess', [$user])->shouldReturn(true);
    }

    public function it_should_restrict_access_for_regular_user_without_subscription_with_a_default_role(User $user): void
    {
        $this->configMock->get('tenant')->willReturn((object) ['config' => (object) ['membersOnlyModeEnabled' => true]]);
        $this->siteMembershipRepositoryMock->getTotalSiteMemberships()->willReturn(1);
        $user->isAdmin()->willReturn(false);
        $this->rolesServiceMock->getRoles($user)->willReturn([new Role(
            RolesEnum::DEFAULT->value,
            RolesEnum::DEFAULT->name,
            []
        )]);
        $this->siteMembershipSubscriptionsServiceMock->hasActiveSiteMembershipSubscription($user)->willReturn(false);
        $this->callOnWrappedObject('shouldRestrictAccess', [$user])->shouldReturn(true);
    }

    public function it_should_handle_exceptions_gracefully(User $user): void
    {
        $this->configMock->get('tenant')->willReturn((object) ['config' => (object) ['membersOnlyModeEnabled' => true]]);
        $this->siteMembershipRepositoryMock->getTotalSiteMemberships()->willThrow(new \Exception('Error'));
        $this->loggerMock->error(Argument::type('string'))->shouldBeCalled();
        $this->callOnWrappedObject('shouldRestrictAccess', [$user])->shouldReturn(false);
    }
}
