<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\MobileConfigs\Repositories;

use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\MobileConfigs\Repositories\MobileConfigRepository;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobilePreviewStatusEnum;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobileSplashScreenTypeEnum;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobileWelcomeScreenLogoTypeEnum;
use Minds\Core\MultiTenant\MobileConfigs\Types\MobileConfig;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Selective\Database\Connection;
use Selective\Database\InsertQuery;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;
use Spec\Minds\Common\Traits\CommonMatchers;

class MobileConfigRepositorySpec extends ObjectBehavior
{
    use CommonMatchers;
    private Collaborator $mysqlHandlerMock;
    private Collaborator $mysqlClientWriterHandlerMock;
    private Collaborator $mysqlClientReaderHandlerMock;
    private Collaborator $loggerMock;
    private Collaborator $configMock;

    public function let(
        MySQLClient $mysqlClient,
        Logger      $logger,
        Config      $config,
        Connection  $mysqlMasterConnectionHandler,
        Connection  $mysqlReaderConnectionHandler,
        PDO         $mysqlMasterConnection,
        PDO         $mysqlReaderConnection,
    ): void {
        $this->mysqlHandlerMock = $mysqlClient;

        $this->mysqlHandlerMock->getConnection(MySQLClient::CONNECTION_MASTER)
            ->willReturn($mysqlMasterConnection);
        $mysqlMasterConnectionHandler->getPdo()->willReturn($mysqlMasterConnection);
        $this->mysqlClientWriterHandlerMock = $mysqlMasterConnectionHandler;

        $this->mysqlHandlerMock->getConnection(MySQLClient::CONNECTION_REPLICA)
            ->willReturn($mysqlReaderConnection);
        $mysqlReaderConnectionHandler->getPdo()->willReturn($mysqlReaderConnection);
        $this->mysqlClientReaderHandlerMock = $mysqlReaderConnectionHandler;

        $this->loggerMock = $logger;
        $this->configMock = $config;

        $this->beConstructedWith(
            $this->mysqlHandlerMock,
            $this->configMock,
            $this->loggerMock,
            $this->mysqlClientReaderHandlerMock,
            $this->mysqlClientWriterHandlerMock,
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(MobileConfigRepository::class);
    }

    public function it_should_store_mobile_configs(InsertQuery $insertQueryMock): void
    {
        $tenantId = 1;
        $splashScreenType = MobileSplashScreenTypeEnum::CONTAIN;
        $welcomeScreenLogoType = MobileWelcomeScreenLogoTypeEnum::HORIZONTAL;
        $previewStatus = null;
        $appVersion = '2';
        $productionAppVersion = '1';
        $appTrackingMessageEnabled = true;
        $appTrackingMessage = 'message';

        $this->mysqlClientWriterHandlerMock->insert()
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->into('minds_tenant_mobile_configs')
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->set([
            'tenant_id' => $tenantId,
            'splash_screen_type' => $splashScreenType->value,
            'welcome_screen_logo_type' => $welcomeScreenLogoType->value,
            'preview_status' => null,
            'preview_last_updated_timestamp' => null,
            'production_app_version' => $productionAppVersion,
            'app_version' => $appVersion,
            'app_tracking_message_enabled' => 1,
            'app_tracking_message' => $appTrackingMessage
        ])
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->onDuplicateKeyUpdate([
            'splash_screen_type' => $splashScreenType->value,
            'welcome_screen_logo_type' => $welcomeScreenLogoType->value,
            'preview_status' => new RawExp('preview_status'),
            'preview_last_updated_timestamp' => null,
            'production_app_version' => $productionAppVersion,
            'app_version' => $appVersion,
            'app_tracking_message_enabled' => 1,
            'app_tracking_message' => $appTrackingMessage
        ])
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->storeMobileConfig(
            $tenantId,
            $splashScreenType,
            $welcomeScreenLogoType,
            $previewStatus,
            $appVersion,
            $productionAppVersion,
            $appTrackingMessageEnabled,
            $appTrackingMessage
        );
    }

    public function it_should_get_mobile_config(
        SelectQuery $selectQuery,
        PDOStatement $stmt
    ): void {
        $tenantId = 1;

        $this->mysqlClientReaderHandlerMock->select()
            ->shouldBeCalledOnce()
            ->willReturn($selectQuery);

        
        $selectQuery->from('minds_tenant_mobile_configs')
            ->shouldBeCalledOnce()
            ->willReturn($selectQuery);

        $selectQuery->where('tenant_id', Operator::EQ, $tenantId)
            ->shouldBeCalledOnce()
            ->willReturn($selectQuery);

        $selectQuery->execute()
            ->shouldBeCalledOnce()
            ->willReturn($stmt);

        $stmt->rowCount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $stmt->fetch(PDO::FETCH_ASSOC)
            ->shouldBeCalledOnce()
            ->willReturn([
                'tenant_id' => $tenantId,
                'splash_screen_type' => MobileSplashScreenTypeEnum::CONTAIN->value,
                'welcome_screen_logo_type' => MobileWelcomeScreenLogoTypeEnum::SQUARE->value,
                'preview_status' => MobilePreviewStatusEnum::NO_PREVIEW->value,
                'preview_last_updated_timestamp' => null,
                'production_app_version' => '1',
                'app_version' => '2',
                'app_tracking_message_enabled' => 1,
                'app_tracking_message' => 'message',
                'update_timestamp' => date('c', time()),
                'eas_project_id' => null,
                'app_slug' => null,
                'app_scheme' => null,
                'app_ios_bundle' => null,
                'app_android_package' => null,
                'android_keystore_fingerprint' => null,
                'apple_development_team_id' => null,
                'app_tracking_message_enabled' => null,
                'app_tracking_message' => null
            ]);

        $this->getMobileConfig($tenantId)
            ->shouldBeAnInstanceOf(MobileConfig::class);
    }
}
