<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Checkout;

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

        #region Services
        (new Services\ServicesProvider())->register();
        #endregion

        #region Input type factories
        (new Types\Factories\FactoriesProvider())->register();
        #endregion

        #region Delegates
        (new Delegates\DelegatesProvider())->register();
        #endregion
    }
}
