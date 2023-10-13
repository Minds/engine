<?php

declare(strict_types=1);

namespace Minds\Core\MultiTenant\Configs;

use Minds\Core\Config\Config;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme;
use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;
use Minds\Exceptions\NotFoundException;

class Manager
{
    public function __construct(
        private readonly MultiTenantDataService $multiTenantDataService,
        private readonly DomainService $domainService,
        private readonly Repository $repository,
        private readonly Logger $logger,
        private readonly Config $config
    ) {
    }

    public function getConfigs(
    ): ?MultiTenantConfig {
        $tenantId = $this->config->get('tenant_id');

        try {
            return $this->repository->get(
                tenantId: $tenantId,
            );
        } catch(NotFoundException $e) {
            return null;
        } catch(\Exception $e) {
            $this->logger->error($e);
            return null;
        }
    }

    public function upsertConfigs(
        ?string $siteName,
        ?MultiTenantColorScheme $colorScheme,
        ?string $primaryColor
    ): bool {
        $tenantId = $this->config->get('tenant_id');

        $result = $this->repository->upsert(
            tenantId: $tenantId,
            siteName: $siteName,
            colorScheme: $colorScheme,
            primaryColor: $primaryColor,
        );

        if ($result) {
            $this->invalidateCache($tenantId);
        }

        return $result;
    }

    private function invalidateCache(int $tenantId): void
    {
        $tenant = $this->multiTenantDataService->getTenantFromId($tenantId);
        $this->domainService->invalidateCache($tenant->domain);
    }
}
