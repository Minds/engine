<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Deployments;

use GuzzleHttp\Client;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\MultiTenant\MobileConfigs\Deployments\Builds\MobilePreviewHandler;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;

;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            MobilePreviewHandler::class,
            fn (Di $di): MobilePreviewHandler => new MobilePreviewHandler(
                httpClient: new Client(),
                config: $di->get(Config::class),
                multiTenantBootService: $di->get(MultiTenantBootService::class)
            )
        );
    }
}
