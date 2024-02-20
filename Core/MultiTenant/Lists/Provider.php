<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Lists;

use Minds\Core\Di\ImmutableException;

class Provider extends \Minds\Core\Di\Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        (new Controllers\ControllersProvider())->register();
        (new Services\ServicesProvider())->register();
        (new Repositories\RepositoriesProvider())->register();
    }
}
