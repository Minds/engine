<?php

namespace Spec\Minds\Core\MultiTenant\Services;

use Aws\S3\S3Client;
use Minds\Core\Config\Config;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Cache\MultiTenantCacheHandler;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Repository;
use Minds\Core\MultiTenant\Services\TenantLifecyleService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class TenantLifecyleServiceSpec extends ObjectBehavior
{
    private Collaborator $repositoryMock;
    private Collaborator $multiTenantCacheHandlerMock;
    private Collaborator $loggerMock;
    private Collaborator $configMock;
    private Collaborator $s3ClientMock;

    public function let(
        Repository $repositoryMock,
        MultiTenantCacheHandler $multiTenantCacheHandlerMock,
        Logger $loggerMock,
        Config $configMock,
        S3Client $s3ClientMock,
    ) {
        $this->beConstructedWith($repositoryMock, $multiTenantCacheHandlerMock, $loggerMock, $configMock, $s3ClientMock);
        $this->repositoryMock = $repositoryMock;
        $this->multiTenantCacheHandlerMock = $multiTenantCacheHandlerMock;
        $this->loggerMock = $loggerMock;
        $this->configMock = $configMock;
        $this->s3ClientMock = $s3ClientMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(TenantLifecyleService::class);
    }

    public function it_should_suspend_expired_trials()
    {
        $tenant1 = new Tenant(1);
        $tenant2 = new Tenant(2);

        $this->repositoryMock->getExpiredTrialsTenants()
            ->shouldBeCalled()
            ->willReturn([$tenant1, $tenant2]);
        
        $this->repositoryMock->suspendTenant(1)
            ->shouldBeCalled()
            ->willReturn(true);
        $this->repositoryMock->suspendTenant(2)
            ->shouldBeCalled()
            ->willReturn(true);

        // Cache should be purged
        $this->multiTenantCacheHandlerMock->resetTenantCache($tenant1)
            ->shouldBeCalled();

        $this->multiTenantCacheHandlerMock->resetTenantCache($tenant2)
            ->shouldBeCalled();

        $this->suspendExpiredTrials();
    }

    public function it_should_delete_suspended_trials()
    {
        $tenant1 = new Tenant(1);
        $tenant2 = new Tenant(2);
    
        $this->repositoryMock->getSuspendedTenants()
            ->shouldBeCalled()
            ->willReturn([$tenant1, $tenant2]);
        
        $this->repositoryMock->deleteTenant(1)
            ->shouldBeCalled()
            ->willReturn(true);
        $this->repositoryMock->deleteTenant(2)
            ->shouldBeCalled()
            ->willReturn(true);

        // S3 should be deleted

        $this->configMock->get('storage')
            ->willReturn([
                'oci_bucket_name' => 'bucket_name'
            ]);
        $this->configMock->get('dataroot')
            ->willReturn('/data/');
        
        $this->s3ClientMock->deleteMatchingObjects('bucket_name', 'data/tenant/1')
            ->shouldBeCalled();
        $this->s3ClientMock->deleteMatchingObjects('bucket_name', 'data/tenant/2')
            ->shouldBeCalled();

        // Cache should be purged
        $this->multiTenantCacheHandlerMock->resetTenantCache($tenant1)
            ->shouldBeCalled();

        $this->multiTenantCacheHandlerMock->resetTenantCache($tenant2)
            ->shouldBeCalled();

        $this->deleteSuspendedTenants();
    }
}
