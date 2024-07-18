<?php
namespace Minds\Core\MultiTenant\Services;

use Aws\S3\S3Client;
use Minds\Core\Config\Config;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Repository;
use Minds\Core\MultiTenant\Cache\MultiTenantCacheHandler;
use Minds\Core\MultiTenant\Models\Tenant;

class TenantLifecyleService
{
    public function __construct(
        private readonly Repository              $repository,
        private readonly MultiTenantCacheHandler $multiTenantCacheHandler,
        private readonly Logger                  $logger,
        private readonly Config                  $config,
        private readonly S3Client                $s3Client,
    ) {
    }

    /**
     * Iterates through all expired trials and marks them as suspended
     */
    public function suspendExpiredTrials(): void
    {
        foreach ($this->repository->getExpiredTrialsTenants() as $tenant) {
            $suspended = $this->suspendTenant($tenant);

            if ($suspended) {
                $this->logger->info($tenant->id . ' was suspended');
            } else {
                $this->logger->error($tenant->id . ' failed to suspend');
            }
        }
    }

    /**
     * Suspends a tenant and clears the cache
     */
    public function suspendTenant(Tenant $tenant): bool
    {
        $tenant->suspendedTimestamp = time();

        if (!$this->repository->suspendTenant($tenant->id)) {
            return false;
        }

        $this->multiTenantCacheHandler->resetTenantCache(tenant: $tenant);

        return true;
    }
    
    /**
     * Iterates through all suspended trial thats should be deleted
     * NOTE: We currently only delete the s3 directory, videos are persisted until
     * we find a better way to index them.
     */
    public function deleteSuspendedTenants(): void
    {
        foreach ($this->repository->getSuspendedTenants() as $tenant) {
            $this->deleteTenant($tenant);
        }
    }

    /**
     * Delete a tenant
     */
    public function deleteTenant(Tenant $tenant): bool
    {
        $tenant->deletedTimestamp = time();

        if (!$this->repository->deleteTenant($tenant->id)) {
            return false;
        }
        $this->logger->info($tenant->id . ' mysql data deleted');

        // Remove all the S3 data
        $this->deleteS3Directory($tenant);
        $this->logger->info($tenant->id . ' deleted s3 directory');

        $this->multiTenantCacheHandler->resetTenantCache(tenant: $tenant);

        return true;
    }

    /**
     * Bulk delete the directory
     */
    private function deleteS3Directory(Tenant $tenant): void
    {
        $bucket = $this->config->get('storage')['oci_bucket_name'];
        $prefix = ltrim($this->config->get('dataroot') . "tenant/{$tenant->id}", '/');

        $this->s3Client->deleteMatchingObjects($bucket, $prefix);
    }
}
