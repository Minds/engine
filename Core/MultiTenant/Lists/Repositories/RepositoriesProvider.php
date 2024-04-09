<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Lists\Repositories;

use InvalidArgumentException;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\MultiTenant\Lists\Enums\TenantListRepositoryTypeEnum;

class RepositoriesProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            TenantListRepositoryInterface::class,
            function (Di $di, array $args): TenantListRepositoryInterface {
                return match ($args['repositoryType']) {
                    TenantListRepositoryTypeEnum::CHANNELS => new TenantChannelsListRepository(
                        mysqlHandler: $di->get('Database\MySQL\Client'),
                        config: $di->get(Config::class),
                        logger: $di->get('Logger')
                    ),
                    TenantListRepositoryTypeEnum::GROUPS => new TenantGroupsListRepository(
                        mysqlHandler: $di->get('Database\MySQL\Client'),
                        config: $di->get(Config::class),
                        logger: $di->get('Logger')
                    ),
                    default => throw new InvalidArgumentException('Invalid repository type')
                };
            }
        );
    }
}
