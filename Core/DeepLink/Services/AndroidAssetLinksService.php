<?php
declare(strict_types=1);

namespace Minds\Core\DeepLink\Services;

use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigReaderService;
use Minds\Exceptions\ServerErrorException;

/**
 * Service for the Android asset links file.
 */
class AndroidAssetLinksService
{
    public function __construct(
        private readonly MobileConfigReaderService $mobileConfigReaderService
    ) {
    }

    /**
     * Get the asset links file.
     * @throws ServerErrorException If the android keystore fingerprint is not set.
     * @return array The asset links file.
     */
    public function get(): array
    {
        $configs = $this->mobileConfigReaderService->getMobileConfig();

        if (!($androidKeystoreFingerprint = $configs?->androidKeystoreFingerprint)) {
            throw new ServerErrorException("Android keystore fingerprint is not set");
        }

        return [[
          "relation" => ["delegate_permission/common.handle_all_urls"],
          "target" => [
              "namespace" => "android_app",
              "package_name" => "com.minds.mobile",
              "sha256_cert_fingerprints" => [$androidKeystoreFingerprint]
          ]
        ]];
    }
}
