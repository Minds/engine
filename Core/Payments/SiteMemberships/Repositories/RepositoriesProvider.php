<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Repositories;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider;

class RepositoriesProvider extends Provider
{
    public function register(): void
    {
        $this->di->bind(
            SiteMembershipRepository::class,
            fn (Di $di): SiteMembershipRepository => new SiteMembershipRepository(
                mysqlHandler: $di->get('Database\MySQL\Client'),
                config: $di->get(Config::class),
                logger: $di->get('Logger')
            )
        );
        $this->di->bind(
            SiteMembershipGroupsRepository::class,
            fn (Di $di): SiteMembershipGroupsRepository => new SiteMembershipGroupsRepository(
                mysqlHandler: $di->get('Database\MySQL\Client'),
                config: $di->get(Config::class),
                logger: $di->get('Logger')
            )
        );
        $this->di->bind(
            SiteMembershipRolesRepository::class,
            fn (Di $di): SiteMembershipRolesRepository => new SiteMembershipRolesRepository(
                mysqlHandler: $di->get('Database\MySQL\Client'),
                config: $di->get(Config::class),
                logger: $di->get('Logger')
            )
        );
        $this->di->bind(
            SiteMembershipSubscriptionsRepository::class,
            fn (Di $di): SiteMembershipSubscriptionsRepository => new SiteMembershipSubscriptionsRepository(
                mysqlHandler: $di->get('Database\MySQL\Client'),
                config: $di->get(Config::class),
                logger: $di->get('Logger')
            )
        );
    }
}
