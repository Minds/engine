<?php
declare(strict_types=1);

namespace Minds\Core\DeepLink;

use Minds\Core\DeepLink\Controllers\WellKnownPsrController;
use Minds\Core\DeepLink\Services\AndroidAssetLinksService;
use Minds\Core\DeepLink\Services\AppleAppSiteAssociationService;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigReaderService;

class Provider extends DiProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->di->bind(WellKnownPsrController::class, function (Di $di): WellKnownPsrController {
            return new WellKnownPsrController(
                androidAssetLinksService: $di->get(AndroidAssetLinksService::class),
                appleAppSiteAssociationService: $di->get(AppleAppSiteAssociationService::class)
            );
        });

        $this->di->bind(AndroidAssetLinksService::class, function (Di $di): AndroidAssetLinksService {
            return new AndroidAssetLinksService(
                mobileConfigReaderService: $di->get(MobileConfigReaderService::class)
            );
        });

        $this->di->bind(AppleAppSiteAssociationService::class, function (Di $di): AppleAppSiteAssociationService {
            return new AppleAppSiteAssociationService(
                mobileConfigReaderService: $di->get(MobileConfigReaderService::class)
            );
        });
    }
}
