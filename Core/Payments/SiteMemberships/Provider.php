<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships;

use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Payments\SiteMemberships\Controllers\ControllersProvider;
use Minds\Core\Payments\SiteMemberships\Repositories\RepositoriesProvider;
use Minds\Core\Payments\SiteMemberships\Services\ServicesProvider;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        (new ControllersProvider())->register();

        (new ServicesProvider())->register();

        (new RepositoriesProvider())->register();
    }
}
