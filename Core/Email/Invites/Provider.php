<?php
declare(strict_types=1);

namespace Minds\Core\Email\Invites;

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
        #region Controllers
        (new Controllers\ControllersProvider())->register();
        #endregion

        #region Repositories
        (new Repositories\RepositoriesProvider())->register();
        #endregion

        #region Services
        (new Services\ServicesProvider())->register();
        #endregion
    }
}
