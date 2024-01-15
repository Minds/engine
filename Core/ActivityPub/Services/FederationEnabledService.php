<?php
declare(strict_types=1);

namespace Minds\Core\ActivityPub\Services;

use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;

/**
 * Service to determine whether federation is enabled.
 */
class FederationEnabledService
{
    public function __construct(
        private MultiTenantBootService $multiTenantBootService,
        private Config $config
    ) {
    }

    /**
     * Whether federation is enabled.
     * @return boolean - whether federation is enabled.
     */
    public function isEnabled(): bool
    {
        if (!(bool) $this->config->get('tenant_id')) {
            return true;
        }

        $tenant = $this->multiTenantBootService->getTenant() ?? null;

        if (!$tenant) {
            return true;
        }

        if ($tenant->config?->federationDisabled) {
            return false;
        }

        if (!isset($tenant->domain)) {
            return false;
        }

        return true;
    }
}
