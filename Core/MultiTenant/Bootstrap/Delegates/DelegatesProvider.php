<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Delegates;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\MultiTenant\Bootstrap\Delegates\ActivityCreationDelegate;
use Minds\Core\MultiTenant\Bootstrap\Delegates\UpdateConfigDelegate;
use Minds\Core\MultiTenant\Bootstrap\Delegates\UpdateLogosDelegate;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\HorizontalLogoExtractor;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\MetadataExtractor;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\MobileSplashLogoExtractor;
use Minds\Core\MultiTenant\Configs\Manager as MultiTenantConfigManager;
use Minds\Core\MultiTenant\Configs\Image\Manager as ConfigImageManager;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigAssetsService;
use Minds\Core\Security\ACL;
use Minds\Core\Sockets\Events as SocketEvents;

class DelegatesProvider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
      
        $this->di->bind(
            ActivityCreationDelegate::class,
            function (Di $di): ActivityCreationDelegate {
                return new ActivityCreationDelegate(
                    activityManager: $di->get('Feeds\Activity\Manager'),
                    metadataExtractor: $di->get(MetadataExtractor::class),
                    acl: $di->get(ACL::class),
                    logger: $di->get('Logger')
                );
            }
        );

        $this->di->bind(
            UpdateConfigDelegate::class,
            function (Di $di): UpdateConfigDelegate {
                return new UpdateConfigDelegate(
                    multiTenantConfigManager: $di->get(MultiTenantConfigManager::class)
                );
            }
        );

        $this->di->bind(
            UpdateUserNameDelegate::class,
            function (Di $di): UpdateUserNameDelegate {
                return new UpdateUserNameDelegate(
                    saveAction: new Save(),
                    logger: $di->get('Logger'),
                    acl: $di->get(ACL::class)
                );
            }
        );

        $this->di->bind(
            UpdateLogosDelegate::class,
            function (Di $di): UpdateLogosDelegate {
                return new UpdateLogosDelegate(
                    configImageManager: $di->get(ConfigImageManager::class),
                    mobileConfigAssetsService: $di->get(MobileConfigAssetsService::class),
                    horizontalLogoExtractor: $di->get(HorizontalLogoExtractor::class),
                    mobileSplashLogoExtractor: $di->get(MobileSplashLogoExtractor::class),
                    logger: $di->get('Logger')
                );
            }
        );

        $this->di->bind(
            ContentGeneratedSocketDelegate::class,
            function (Di $di): ContentGeneratedSocketDelegate {
                return new ContentGeneratedSocketDelegate(
                    socketEvents: new SocketEvents(),
                    config: $di->get(Config::class),
                    logger: $di->get('Logger')
                );
            }
        );
    }
}
