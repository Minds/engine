<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\MobileConfigs\Services;

use Minds\Core\Config\Config;
use Minds\Core\GraphQL\Types\KeyValuePair;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme;
use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobileConfigImageTypeEnum;
use Minds\Core\MultiTenant\MobileConfigs\Exceptions\NoMobileConfigFoundException;
use Minds\Core\MultiTenant\MobileConfigs\Repositories\MobileConfigRepository;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigReaderService;
use Minds\Core\MultiTenant\MobileConfigs\Types\AppReadyMobileConfig;
use Minds\Core\MultiTenant\MobileConfigs\Types\MobileConfig;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use PhpSpec\Exception\Example\FailureException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Spec\Minds\Common\Traits\CommonMatchers;

class MobileConfigReaderServiceSpec extends ObjectBehavior
{
    use CommonMatchers;

    private Collaborator $mobileConfigRepositoryMock;
    private Collaborator $multiTenantBootServiceMock;
    private Collaborator $configMock;

    public function let(
        MobileConfigRepository $mobileConfigRepository,
        MultiTenantBootService $multiTenantBootService,
        Config                 $config
    ): void {
        $this->mobileConfigRepositoryMock = $mobileConfigRepository;
        $this->multiTenantBootServiceMock = $multiTenantBootService;
        $this->configMock = $config;

        $this->beConstructedWith(
            $this->mobileConfigRepositoryMock,
            $this->multiTenantBootServiceMock,
            $this->configMock
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(MobileConfigReaderService::class);
    }

    public function it_should_get_app_ready_mobile_config(): void
    {
        $appTrackingMessage = 'test';

        $tenantMock = new Tenant(
            id: 1,
            config: new MultiTenantConfig(
                siteName: 'test',
                colorScheme: MultiTenantColorScheme::DARK,
                primaryColor: 'test',
                isNonProfit: true
            )
        );
        $mobileConfigMock = new MobileConfig(
            updateTimestamp: time(),
            appTrackingMessageEnabled: true,
            appTrackingMessage: $appTrackingMessage,
        );
        $md5 = md5(strval($tenantMock->id));
        $siteUrl = 'https://example.minds.com/';

        $this->multiTenantBootServiceMock->bootFromTenantId(1)
            ->shouldBeCalledOnce();

        $this->multiTenantBootServiceMock->getTenant()
            ->shouldBeCalledOnce()
            ->willReturn($tenantMock);

        $this->mobileConfigRepositoryMock->getMobileConfig(1)
            ->shouldBeCalledOnce()
            ->willThrow(NoMobileConfigFoundException::class);

        $this->configMock->get('site_url')
            ->shouldBeCalled()
            ->willReturn($siteUrl);

        $this->multiTenantBootServiceMock->resetRootConfigs()
            ->shouldBeCalledOnce();

        /** @var AppReadyMobileConfig $response */
        $response = $this->getAppReadyMobileConfig(1);

        $response->shouldBeAnInstanceOf(AppReadyMobileConfig::class);

        $response->appName->shouldBe('test');
        $response->tenantId->shouldBe(1);
        $response->appHost->shouldBe('example.minds.com');
        $response->appSplashResize->shouldBe(strtolower($mobileConfigMock->splashScreenType->name));
        $response->accentColorLight->shouldBe('test');
        $response->accentColorDark->shouldBe('test');
        $response->welcomeLogoType->shouldBe(strtolower($mobileConfigMock->welcomeScreenLogoType->name));
        $response->theme->shouldBe(strtolower($tenantMock->config->colorScheme->value));
        $response->apiUrl->shouldBe($siteUrl);
        $response->appTrackingMessage->shouldBe(MobileConfig::DEFAULT_APP_TRACKING_MESSAGE);
        $response->isNonProfit->shouldBe(true);
        $response->membersOnlyModeEnabled->shouldBe(false);

        $imageTypes = array_map(fn (MobileConfigImageTypeEnum $imageType): string => $imageType->value, MobileConfigImageTypeEnum::cases());

        $response->getAssets()->shouldCompleteCallback(function (array $assets) use ($imageTypes, $siteUrl, $md5): bool {
            if (count($assets) !== count($imageTypes)) {
                throw new FailureException("The total amount of returned assets (" . count($assets) . ") does not match the total amount of image types (" . count($imageTypes) . ")");
            }
            foreach ($assets as $asset) {
                if (!($asset instanceof KeyValuePair)) {
                    throw new FailureException("The asset is not an instance of KeyValuePair");
                }
                if (!in_array($asset->key, $imageTypes, true)) {
                    throw new FailureException("The asset key (" . $asset->key . ") is not a valid image type");
                }
                if (!str_starts_with($asset->value, "https://{$md5}.networks.minds.com/api/v3/multi-tenant/mobile-configs/image/$asset->key?")) {
                    throw new FailureException("The asset value (" . $asset->value . ") does not match the expected value ({$siteUrl}api/v3/multi-tenant/mobile-configs/image/$asset->key)");
                }
            }
            return true;
        });
    }
}
