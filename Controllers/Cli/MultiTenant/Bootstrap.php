<?php
declare(strict_types=1);

namespace Minds\Controllers\Cli\MultiTenant;

use Minds\Cli\Controller;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\MultiTenant\Bootstrap\Services\MultiTenantBootstrapService;
use Minds\Exceptions\CliException;
use Minds\Interfaces\CliControllerInterface;

class Bootstrap extends Controller implements CliControllerInterface
{
    public function __construct(
        private ?MultiTenantBootstrapService $service = null,
    ) {
        Di::_()->get(Config::class)->set('min_log_level', 'info');
        $this->service ??= Di::_()->get(MultiTenantBootstrapService::class);
    }

    public function help($command = null)
    {
    }

    /**
     * Bootstrap a new tenant.
     * @example
     * - php cli.php MultiTenant Bootstrap --tenantId=123 --siteUrl=https://www.minds.com/
     * @return void
     * @throws GraphQLException
     */
    public function exec(): void
    {
        $tenantId = $this->getOpt('tenantId') ? (int) $this->getOpt('tenantId') : null;
        $siteUrl = $this->getOpt('siteUrl');
        
        if (!$tenantId || $tenantId < 1) {
            throw new CliException('Tenant ID is a required parameter');
        }

        if (!$siteUrl) {
            throw new CliException('Site URL is a required parameter');
        }

        $this->service->bootstrap($siteUrl, $tenantId);
    }


}
