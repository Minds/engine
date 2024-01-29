<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    /**
     * @inheritDoc
     */
    public function register()
    {
        #region Controllers
        (new \Minds\Core\Payments\SiteMemberships\Controllers\ControllersProvider())->register();
        #endregion

        #region Services
        (new \Minds\Core\Payments\SiteMemberships\Services\ServicesProvider())->register();
        #endregion
    }
}
