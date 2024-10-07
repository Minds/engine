<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Repositories;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobilePreviewStatusEnum;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobileSplashScreenTypeEnum;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobileWelcomeScreenLogoTypeEnum;
use Minds\Core\MultiTenant\MobileConfigs\Exceptions\NoMobileConfigFoundException;
use Minds\Core\MultiTenant\MobileConfigs\Types\MobileConfig;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class MobileConfigRepository extends AbstractRepository
{
    private const TABLE_NAME = 'minds_tenant_mobile_configs';

    /**
     * @param int|null $tenantId
     * @param MobileSplashScreenTypeEnum|null $splashScreenType
     * @param MobileWelcomeScreenLogoTypeEnum|null $welcomeScreenLogoType
     * @param MobilePreviewStatusEnum|null $previewStatus
     * @param string|null $productionAppVersion - app version of tenant app in production.
     * @param string|null $appVersion - app version for previewer.
     * @param bool|null $appTrackingMessageEnabled
     * @param string|null $appTrackingMessage
     * @return void
     */
    public function storeMobileConfig(
        ?int                             $tenantId = null,
        ?MobileSplashScreenTypeEnum      $splashScreenType = null,
        ?MobileWelcomeScreenLogoTypeEnum $welcomeScreenLogoType = null,
        ?MobilePreviewStatusEnum         $previewStatus = null,
        ?string                          $appVersion = null,
        ?string                          $productionAppVersion = null,
        ?bool                            $appTrackingMessageEnabled = null,
        ?string                          $appTrackingMessage = null
    ): void {
        $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'tenant_id' => $tenantId ?? ($this->config->get('tenant_id') ?? -1),
                'splash_screen_type' => $splashScreenType?->value ?? MobileSplashScreenTypeEnum::CONTAIN->value,
                'welcome_screen_logo_type' => $welcomeScreenLogoType?->value ?? MobileWelcomeScreenLogoTypeEnum::SQUARE->value,
                'preview_status' => $previewStatus?->value ?? MobilePreviewStatusEnum::NO_PREVIEW->value,
                'preview_last_updated_timestamp' => $previewStatus ? date('c', time()) : null,
                'production_app_version' => $productionAppVersion,
                'app_version' => $appVersion,
                'app_tracking_message_enabled' => $appTrackingMessageEnabled !== null ? (int) $appTrackingMessageEnabled : null,
                'app_tracking_message' => $appTrackingMessage
            ])
            ->onDuplicateKeyUpdate([
                'splash_screen_type' => $splashScreenType ? $splashScreenType->value : new RawExp('splash_screen_type'),
                'welcome_screen_logo_type' => $welcomeScreenLogoType ? $welcomeScreenLogoType->value : new RawExp('welcome_screen_logo_type'),
                'preview_status' => $previewStatus ? $previewStatus->value : new RawExp('preview_status'),
                'preview_last_updated_timestamp' => $previewStatus ? date('c', time()) : null,
                'production_app_version' => $productionAppVersion,
                'app_version' => $appVersion,
                'app_tracking_message_enabled' => $appTrackingMessageEnabled !== null ? (int) $appTrackingMessageEnabled : null,
                'app_tracking_message' => $appTrackingMessage
            ])
            ->execute();
    }

    /**
     * @param int|null $tenantId
     * @return MobileConfig
     * @throws NoMobileConfigFoundException
     */
    public function getMobileConfig(
        ?int $tenantId = null
    ): MobileConfig {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->where('tenant_id', Operator::EQ, $tenantId ?? ($this->config->get('tenant_id') ?? -1))
            ->execute();

        if ($stmt->rowCount() === 0) {
            throw new NoMobileConfigFoundException();
        }

        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        return new MobileConfig(
            updateTimestamp: strtotime($entry['update_timestamp']),
            splashScreenType: $entry['splash_screen_type'] ? MobileSplashScreenTypeEnum::tryFrom($entry['splash_screen_type']) : null,
            welcomeScreenLogoType: $entry['welcome_screen_logo_type'] ? MobileWelcomeScreenLogoTypeEnum::tryFrom($entry['welcome_screen_logo_type']) : null,
            previewStatus: MobilePreviewStatusEnum::tryFrom($entry['preview_status']),
            previewLastUpdatedTimestamp: $entry['preview_last_updated_timestamp'] ? strtotime($entry['preview_last_updated_timestamp']) : null,
            productionAppVersion: $entry['production_app_version'] ?? null,
            appVersion: $entry['app_version'],
            easProjectId: $entry['eas_project_id'],
            appSlug: $entry['app_slug'],
            appScheme: $entry['app_scheme'],
            appIosBundle: $entry['app_ios_bundle'],
            appAndroidPackage: $entry['app_android_package'],
            androidKeystoreFingerprint: $entry['android_keystore_fingerprint'],
            appleDevelopmentTeamId: $entry['apple_development_team_id'],
            appTrackingMessageEnabled: (bool) $entry['app_tracking_message_enabled'],
            appTrackingMessage: $entry['app_tracking_message'] ?? MobileConfig::DEFAULT_APP_TRACKING_MESSAGE,
        );
    }

    /**
     * Clear the mobile production app version for all tenants.
     * @return bool - true on success.
     */
    public function clearAllProductionMobileAppVersions(): bool
    {
        return $this->mysqlClientWriterHandler->update()
            ->table(self::TABLE_NAME)
            ->set(['production_app_version' => null])
            ->execute();
    }
}
