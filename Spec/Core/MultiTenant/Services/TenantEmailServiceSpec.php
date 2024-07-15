<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\Services;

use Minds\Core\Email\V2\Delegates\DigestSender;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;
use Minds\Core\MultiTenant\Services\TenantEmailService;
use Minds\Core\MultiTenant\Services\TenantUsersService;
use Minds\Entities\User;
use Minds\Interfaces\SenderInterface;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class TenantEmailServiceSpec extends ObjectBehavior
{
    private Collaborator $multiTenantBootServiceMock;
    private Collaborator $multiTenantDataServiceMock;
    private Collaborator $multiTenantUsersServiceMock;
    private Collaborator $loggerMock;

    public function let(
        MultiTenantBootService $multiTenantBootServiceMock,
        MultiTenantDataService $multiTenantDataServiceMock,
        TenantUsersService  $multiTenantUsersServiceMock,
        Logger $loggerMock
    ) {
        $this->beConstructedWith(
            $multiTenantBootServiceMock,
            $multiTenantDataServiceMock,
            $multiTenantUsersServiceMock,
            $loggerMock
        );
        $this->multiTenantBootServiceMock = $multiTenantBootServiceMock;
        $this->multiTenantDataServiceMock = $multiTenantDataServiceMock;
        $this->multiTenantUsersServiceMock = $multiTenantUsersServiceMock;
        $this->loggerMock = $loggerMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(TenantEmailService::class);
    }

    public function it_should_send_to_all_users(
        SenderInterface $emailSender,
        User $user1,
        User $user2,
        User $user3
    ) {
        $tenantId = (int) Guid::build();
        $tenant = new Tenant(id: $tenantId);

        $this->multiTenantUsersServiceMock->getUsers(tenantId: $tenantId)
            ->shouldBeCalled()
            ->willReturn([
                $user1,
                $user2,
                $user3
            ]);

        $emailSender->send($user1)
            ->shouldBeCalled();

        $emailSender->send($user2)
            ->shouldBeCalled();

        $emailSender->send($user3)
            ->shouldBeCalled();

        $this->sendToAllUsers($tenant, $emailSender);
    }

    public function it_should_send_to_all_users_across_all_tenants(
        SenderInterface $emailSender,
        User $user1,
        User $user2,
        User $user3
    ) {
        $tenant1Id = (int) Guid::build();
        $tenant2Id = (int) Guid::build();
        $tenant3Id = (int) Guid::build();

        $tenant1 = new Tenant(id: $tenant1Id);
        $tenant2 = new Tenant(id: $tenant2Id);
        $tenant3 = new Tenant(id: $tenant3Id);

        $this->multiTenantDataServiceMock->getTenants(limit: 9999999)
            ->shouldBeCalled()
            ->willReturn([
                $tenant1,
                $tenant2,
                $tenant3
            ]);

        $this->multiTenantUsersServiceMock->getUsers(tenantId: $tenant1Id)
            ->shouldBeCalled()
            ->willReturn([$user1]);

        $this->multiTenantUsersServiceMock->getUsers(tenantId: $tenant2Id)
            ->shouldBeCalled()
            ->willReturn([$user2]);

        $this->multiTenantUsersServiceMock->getUsers(tenantId: $tenant3Id)
            ->shouldBeCalled()
            ->willReturn([$user3]);

        $emailSender->send($user1)
            ->shouldBeCalled();

        $emailSender->send($user2)
            ->shouldBeCalled();

        $emailSender->send($user3)
            ->shouldBeCalled();

        $this->sendToAllUsersAcrossTenants($emailSender);
    }

    public function it_should_NOT_send_email_digest_emails_when_disabled_for_the_tenant(
        DigestSender $emailSender,
        User $user1,
        User $user2,
        User $user3
    ) {
        $tenantId = (int) Guid::build();
        $tenant = new Tenant(
            id: $tenantId,
            config: new MultiTenantConfig(digestEmailEnabled: false)
        );

        $this->multiTenantDataServiceMock->getTenants(limit: 9999999)
            ->shouldBeCalled()
            ->willReturn([$tenant]);

        $this->multiTenantUsersServiceMock->getUsers(tenantId: $tenantId)
            ->shouldNotBeCalled();

        $emailSender->send($user1)
            ->shouldNotBeCalled();

        $emailSender->send($user2)
            ->shouldNotBeCalled();

        $emailSender->send($user3)
            ->shouldNotBeCalled();

        $this->sendToAllUsersAcrossTenants($emailSender);
    }

    public function it_should_send_email_digest_emails_when_enabled_for_the_tenant(
        DigestSender $emailSender,
        User $user1,
        User $user2,
        User $user3
    ) {
        $tenantId = (int) Guid::build();
        $tenant = new Tenant(
            id: $tenantId,
            config: new MultiTenantConfig(digestEmailEnabled: true)
        );

        $this->multiTenantDataServiceMock->getTenants(limit: 9999999)
            ->shouldBeCalled()
            ->willReturn([$tenant]);

        $this->multiTenantUsersServiceMock->getUsers(tenantId: $tenantId)
            ->shouldBeCalled()
            ->willReturn([$user1, $user2, $user3]);

        $emailSender->send($user1)
            ->shouldBeCalled();

        $emailSender->send($user2)
            ->shouldBeCalled();

        $emailSender->send($user3)
            ->shouldBeCalled();

        $this->sendToAllUsersAcrossTenants($emailSender);
    }
}
