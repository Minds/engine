<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Repositories;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\MultiTenant\Enums\MobilePreviewStatusEnum;
use Minds\Core\MultiTenant\Enums\MobileSplashScreenTypeEnum;
use Minds\Core\MultiTenant\Enums\MobileWelcomeScreenLogoTypeEnum;
use Minds\Core\MultiTenant\Exceptions\NoMobileConfigFoundException;
use Minds\Core\MultiTenant\Types\MobileConfig;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class MobileConfigRepository extends AbstractRepository
{
    private const TABLE_NAME = 'minds_tenant_mobile_configs';

    /**
     * @param MobileSplashScreenTypeEnum|null $splashScreenType
     * @param MobileWelcomeScreenLogoTypeEnum|null $welcomeScreenLogoType
     * @param MobilePreviewStatusEnum|null $previewStatus
     * @return void
     */
    public function storeMobileConfig(
        ?int                             $tenantId = null,
        ?MobileSplashScreenTypeEnum      $splashScreenType = null,
        ?MobileWelcomeScreenLogoTypeEnum $welcomeScreenLogoType = null,
        ?MobilePreviewStatusEnum         $previewStatus = null,
    ): void {
        $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'tenant_id' => $tenantId ?? ($this->config->get('tenant_id') ?? -1),
                'splash_screen_type' => $splashScreenType?->value,
                'welcome_screen_logo_type' => $welcomeScreenLogoType?->value,
                'preview_status' => $previewStatus?->value ?? MobilePreviewStatusEnum::NO_PREVIEW->value,
                'preview_last_updated_timestamp' => $previewStatus ? date('c', time()) : null,
            ])
            ->onDuplicateKeyUpdate([
                'splash_screen_type' => $splashScreenType ? $splashScreenType->value : new RawExp('splash_screen_type'),
                'welcome_screen_logo_type' => $welcomeScreenLogoType ? $welcomeScreenLogoType->value : new RawExp('welcome_screen_logo_type'),
                'preview_status' => $previewStatus ? $previewStatus->value : new RawExp('preview_status'),
                'preview_last_updated_timestamp' => $previewStatus ? date('c', time()) : null,
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
        );
    }
}
