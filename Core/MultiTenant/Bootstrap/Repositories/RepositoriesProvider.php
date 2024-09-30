<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Repositories;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Config\Config;

class RepositoriesProvider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            BootstrapProgressRepository::class,
            function (Di $di): BootstrapProgressRepository {
                return new BootstrapProgressRepository(
                    mysqlHandler: $di->get('Database\MySQL\Client'),
                    config: $di->get(Config::class),
                    logger: $di->get('Logger')
                );
            }
        );
    }
}
