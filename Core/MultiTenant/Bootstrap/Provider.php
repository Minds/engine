<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap;

use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        (new Services\ServicesProvider())->register();

        (new Repositories\RepositoriesProvider())->register();

        (new Controllers\ControllersProvider())->register();

        (new Delegates\DelegatesProvider())->register();

        (new Clients\ClientsProvider())->register();
    }
}
