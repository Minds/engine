<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Repositories;

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
            TenantUsersRepository::class,
            function (Di $di): TenantUsersRepository {
                return new TenantUsersRepository(
                    mysqlHandler: $di->get('Database\MySQL\Client'),
                    logger: $di->get('Logger')
                );
            }
        );
        $this->di->bind(
            DomainsRepository::class,
            function (Di $di): DomainsRepository {
                return new DomainsRepository(
                    mysqlHandler: $di->get('Database\MySQL\Client'),
                    logger: $di->get('Logger')
                );
            }
        );
    }
}
