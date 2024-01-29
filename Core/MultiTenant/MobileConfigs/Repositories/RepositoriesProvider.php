<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Repositories;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;

class RepositoriesProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            MobileConfigRepository::class,
            fn (Di $di): MobileConfigRepository => new MobileConfigRepository(
                mysqlHandler: $di->get('Database\MySQL\Client'),
                config: $di->get(Config::class),
                logger: $di->get('Logger')
            )
        );
    }
}
