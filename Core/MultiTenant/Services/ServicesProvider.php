<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Services;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\V2\Membership\Manager as GroupsMembershipManager;
use Minds\Core\Http\Cloudflare\Client as CloudflareClient;
use Minds\Core\MultiTenant\Cache\MultiTenantCacheHandler;
use Minds\Core\MultiTenant\Configs\Manager as MultiTenantConfigManager;
use Minds\Core\MultiTenant\Configs\Repository as TenantConfigRepository;
use Minds\Core\MultiTenant\MobileConfigs\Deployments\Builds\MobilePreviewHandler;
use Minds\Core\MultiTenant\MobileConfigs\Repositories\MobileConfigRepository;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigAssetsService;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigManagementService;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigReaderService;
use Minds\Core\MultiTenant\Repositories\DomainsRepository;
use Minds\Core\MultiTenant\Repositories\FeaturedEntitiesRepository;
use Minds\Core\MultiTenant\Repositories\TenantUsersRepository;
use Minds\Core\MultiTenant\Repository;
use Minds\Core\Notifications\PostSubscriptions\Services\PostSubscriptionsService;

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
                $di->get(MultiTenantCacheHandler::class),
                $di->get(CloudflareClient::class),
                $di->get(DomainsRepository::class),
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
            TenantsService::class,
            function (Di $di): TenantsService {
                return new TenantsService(
                    $di->get(Repository::class),
                    $di->get(TenantConfigRepository::class),
                    $di->get(MultiTenantCacheHandler::class),
                    $di->get(DomainService::class),
                    $di->get('Config'),
                );
            }
        );

        $this->di->bind(
            TenantUsersService::class,
            function (Di $di): TenantUsersService {
                return new TenantUsersService(
                    $di->get(TenantUsersRepository::class),
                    new Save(),
                    $di->get('Config'),
                    $di->get(MultiTenantBootService::class),
                    $di->get('Security\ACL'),
                );
            }
        );

        $this->di->bind(
            FeaturedEntityService::class,
            function (Di $di): FeaturedEntityService {
                return new FeaturedEntityService(
                    $di->get(FeaturedEntitiesRepository::class),
                    $di->get('Config')
                );
            }
        );

        $this->di->bind(
            MobileConfigAssetsService::class,
            fn (Di $di): MobileConfigAssetsService => new MobileConfigAssetsService(
                $di->get('Media\Imagick\Manager'),
                $di->get(Config::class),
                $di->get(MultiTenantBootService::class),
                $di->get(MultiTenantConfigManager::class)
            )
        );

        $this->di->bind(
            MobileConfigReaderService::class,
            fn (Di $di): MobileConfigReaderService => new MobileConfigReaderService(
                mobileConfigRepository: $di->get(MobileConfigRepository::class),
                multiTenantBootService: $di->get(MultiTenantBootService::class),
                config: $di->get(Config::class)
            )
        );

        $this->di->bind(
            MobileConfigManagementService::class,
            fn (Di $di): MobileConfigManagementService => new MobileConfigManagementService(
                mobileConfigRepository: $di->get(MobileConfigRepository::class),
                mobilePreviewHandler: $di->get(MobilePreviewHandler::class),
            )
        );

        $this->di->bind(
            FeaturedEntityAutoSubscribeService::class,
            function (Di $di): FeaturedEntityAutoSubscribeService {
                return new FeaturedEntityAutoSubscribeService(
                    $di->get(FeaturedEntityService::class),
                    $di->get(PostSubscriptionsService::class),
                    $di->get(GroupsMembershipManager::class),
                    $di->get(EntitiesBuilder::class)
                );
            },
            ['useFactory' => true]
        );
    }
}
