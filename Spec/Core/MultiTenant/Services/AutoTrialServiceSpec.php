<?php

namespace Spec\Minds\Core\MultiTenant\Services;

use Minds\Core\Authentication\Services\RegisterService;
use Minds\Core\Email\V2\Campaigns\Recurring\TenantTrial\TenantTrialEmailer;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\AutoTrialService;
use Minds\Core\MultiTenant\Services\TenantsService;
use Minds\Core\MultiTenant\Services\TenantUsersService;
use Minds\Core\MultiTenant\Types\TenantUser;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class AutoTrialServiceSpec extends ObjectBehavior
{
    private Collaborator $registerServiceMock;
    private Collaborator  $tenantsServiceMock;
    private Collaborator  $usersServiceMock;
    private Collaborator  $emailServiceMock;

    public function let(
        RegisterService $registerServiceMock,
        TenantsService $tenantsServiceMock,
        TenantUsersService $usersServiceMock,
        TenantTrialEmailer $emailServiceMock
    ) {
        $this->beConstructedWith($registerServiceMock, $tenantsServiceMock, $usersServiceMock, $emailServiceMock);
        $this->registerServiceMock = $registerServiceMock;
        $this->tenantsServiceMock = $tenantsServiceMock;
        $this->usersServiceMock = $usersServiceMock;
        $this->emailServiceMock = $emailServiceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(AutoTrialService::class);
    }

    public function it_should_start_a_trial_by_email()
    {
        $tenantMock = new Tenant(
            id: 1,
            ownerGuid: -1
        );
    
        $this->tenantsServiceMock->createNetworkTrial(Argument::type(Tenant::class), Argument::type(User::class))
            ->shouldBeCalled()
            ->willReturn($tenantMock);

        $this->usersServiceMock->createNetworkRootUser(Argument::type(TenantUser::class), Argument::type(User::class))
            ->shouldBeCalled();

        $this->emailServiceMock->setUser(Argument::type(User::class))
            ->shouldBeCalled()
            ->willReturn($this->emailServiceMock);

        $this->emailServiceMock->setTenantId(1)
            ->shouldBeCalled()
            ->willReturn($this->emailServiceMock);

        $this->emailServiceMock->setUsername('networkadmin')
            ->shouldBeCalled()
            ->willReturn($this->emailServiceMock);

        $this->emailServiceMock->setPassword(Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn($this->emailServiceMock);

        $this->emailServiceMock->send()
            ->shouldBeCalled()
            ->willReturn(true);

        $tenant = $this->startTrialWithEmail("test@test.com");
    }
}
