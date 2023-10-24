<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Services;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\MultiTenant\Configs\Repository as TenantConfigRepository;
use Minds\Core\MultiTenant\Repositories\TenantUsersRepository;
use Minds\Core\MultiTenant\Repository;

class ServicesProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {

        $this->di->bind(MultiTenantDataService::class, function (Di $di): MultiTenantDataService {
            return new MultiTenantDataService($di->get(Repository::class));
        });

        $this->di->bind(DomainService::class, function (Di $di): DomainService {
            return new DomainService(
                $di->get('Config'),
                $di->get(MultiTenantDataService::class),
                $di->get('Cache\PsrWrapper')
            );
        });

        $this->di->bind(MultiTenantBootService::class, function (Di $di): MultiTenantBootService {
            return new MultiTenantBootService(
                $di->get('Config'),
                $di->get(DomainService::class),
                $di->get(MultiTenantDataService::class),
            );
        }, ['useFactory' => true]);

        $this->di->bind(
            NetworksService::class,
            function (Di $di): NetworksService {
                return new NetworksService(
                    $di->get(Repository::class),
                    $di->get(TenantConfigRepository::class)
                );
            }
        );

        $this->di->bind(
            NetworkUsersService::class,
            function (Di $di): NetworkUsersService {
                return new NetworkUsersService(
                    $di->get(TenantUsersRepository::class),
                    new Save(),
                    $di->get('Config')
                );
            }
        );
    }
}