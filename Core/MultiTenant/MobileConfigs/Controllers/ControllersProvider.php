<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Controllers;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\MultiTenant\MobileConfigs\Helpers\GitlabPipelineJwtTokenValidator;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigAssetsService;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigManagementService;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigReaderService;
use Minds\Core\MultiTenant\MobileConfigs\Services\ProductionAppVersionService;

class ControllersProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            MobileConfigReaderController::class,
            fn (Di $di): MobileConfigReaderController => new MobileConfigReaderController(
                mobileConfigReaderService: $di->get(MobileConfigReaderService::class),
                gitlabPipelineJwtTokenValidator: $di->get(GitlabPipelineJwtTokenValidator::class)
            )
        );

        $this->di->bind(
            MobileConfigPsrController::class,
            fn (Di $di): MobileConfigPsrController => new MobileConfigPsrController(
                mobileConfigAssetsService: $di->get(MobileConfigAssetsService::class),
            )
        );

        $this->di->bind(
            MobileConfigPreviewPsrController::class,
            fn (Di $di): MobileConfigPreviewPsrController => new MobileConfigPreviewPsrController(
                mobileConfigManagementService: $di->get(MobileConfigManagementService::class),
                gitlabPipelineJwtTokenValidator: $di->get(GitlabPipelineJwtTokenValidator::class)
            )
        );

        $this->di->bind(
            MobileConfigManagementController::class,
            fn (Di $di): MobileConfigManagementController => new MobileConfigManagementController(
                mobileConfigManagementService: $di->get(MobileConfigManagementService::class),
                productionAppVersionService: $di->get(ProductionAppVersionService::class),
                gitlabPipelineJwtTokenValidator: $di->get(GitlabPipelineJwtTokenValidator::class),
            )
        );
    }
}
