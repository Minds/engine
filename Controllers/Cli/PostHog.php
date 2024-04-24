<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Analytics\PostHog\PostHogService;
use Minds\Core\Di\Di;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Interfaces;

class PostHog extends Cli\Controller implements Interfaces\CliControllerInterface
{
    protected MultiTenantBootService $tenantBootService;
    protected PostHogService $service;

    public function __construct()
    {
        Di::_()->get('Config')
            ->set('min_log_level', 'INFO');
        $this->tenantBootService ??= Di::_()->get(MultiTenantBootService::class);
        $this->service = Di::_()->get(PostHogService::class);
    }

    public function help($command = null)
    {
        $this->out('TBD');
    }

    public function exec()
    {
        $this->out('See help');
    }

    public function syncCache()
    {
        $this->service->getFeatureFlags(useCache: false);

        $this->tenantBootService->bootFromTenantId(1);

        $this->getService()->getFeatureFlags(useCache: false);
    }

    private function getService(): PostHogService
    {
        return $this->service ?:  $this->service = Di::_()->get(PostHogService::class);
    }
}
