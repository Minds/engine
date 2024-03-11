<?php
declare(strict_types=1);

namespace Minds\Core\PWA\Services;

use Minds\Core\Config\Config;
use Minds\Core\PWA\Models\AbstractPWAManifest;
use Minds\Core\PWA\Models\MindsManifest;
use Minds\Core\PWA\Models\TenantManifest;

/**
 * PWA Manifest Service
 */
class ManifestService
{
    public function __construct(
        private Config $config
    ) {
    }

    /**
     * Factory function to get the appropriate manifest based on whether
     * the request is coming from a tenant network.
     * @return AbstractPWAManifest - the appropriate manifest.
     */
    public function getManifest(): AbstractPWAManifest
    {
        return (bool) $this->config->get('tenant_id') ?
            new TenantManifest() :
            new MindsManifest();
    }
}
