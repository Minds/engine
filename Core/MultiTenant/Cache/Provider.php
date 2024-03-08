<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Cache;

use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->di->bind(MultiTenantCacheHandler::class, function (Di $di): MultiTenantCacheHandler {
            return new MultiTenantCacheHandler($di->get('Cache\PsrWrapper'));
        });
    }
}
