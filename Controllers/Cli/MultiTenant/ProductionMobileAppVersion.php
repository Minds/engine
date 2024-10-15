<?php
declare(strict_types=1);

namespace Minds\Controllers\Cli\MultiTenant;

use Minds\Cli\Controller;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\MultiTenant\MobileConfigs\Services\ProductionAppVersionService;
use Minds\Exceptions\CliException;
use Minds\Interfaces\CliControllerInterface;

/**
 * Production mobile app version CLI controller.
 */
class ProductionMobileAppVersion extends Controller implements CliControllerInterface
{
    public function __construct(
        private ?ProductionAppVersionService $service = null
    ) {
        Di::_()->get(Config::class)->set('min_log_level', 'info');
        $this->service ??= Di::_()->get(ProductionAppVersionService::class);
    }

    public function help($command = null)
    {
        $this->out("Usage:
            - php cli.php MultiTenant ProductionMobileAppVersion set --tenantId=<tenantId> --version=<version>
            - php cli.php MultiTenant ProductionMobileAppVersion clearForAllTenants
        ");
    }

    public function exec(): void
    {
        $this->help();
    }

    /**
     * Set the production mobile app version for a tenant.
     * @return void
     */
    public function set(): void
    {
        $tenantId = $this->getOpt('tenantId') ? (int) $this->getOpt('tenantId') : null;
        $version = $this->getOpt('version') ?? null;

        if (!$tenantId || $tenantId < 1) {
            throw new CliException('tenantId is a required parameter');
        }

        if (!$version) {
            throw new CliException('version is a required parameter');
        }

        $this->service->setProductionMobileAppVersion($tenantId, $version);
    }

    /**
     * Clear the production mobile app version for all tenants.
     * @return void
     */
    public function clearForAllTenants(): void
    {
        $this->service->clearForAllTenants();
    }
}
