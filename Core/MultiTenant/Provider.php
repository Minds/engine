<?php

namespace Minds\Core\MultiTenant;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
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
        ####### Controllers #######
        (new Controllers\ControllersProvider())->register();

        ####### Services #######
        (new Services\ServicesProvider())->register();

        ####### Repositories #######
        (new Repositories\RepositoriesProvider())->register();

        ####### Types Factories #######
        (new Types\Factories\FactoriesProvider())->register();

        ####### Other dependencies #######
        $this->di->bind(Repository::class, function (Di $di): Repository {
            return new Repository(
                mysqlHandler: $di->get('Database\MySQL\Client'),
                config: $di->get(Config::class),
                logger: $di->get('Logger')
            );
        });

        (new Cache\Provider())->register();

        #region Deployments
        (new MobileConfigs\Deployments\Provider())->register();
        #endregion
    }
}
