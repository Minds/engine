<?php

namespace Spec\Minds\Core\DeepLink\Services;

use Minds\Core\Config\Config;
use Minds\Core\DeepLink\Services\AndroidAssetLinksService;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigReaderService;
use Minds\Core\MultiTenant\MobileConfigs\Types\MobileConfig;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use ReflectionClass;

class AndroidAssetLinksServiceSpec extends ObjectBehavior
{
    private Collaborator $mobileConfigReaderServiceMock;
    private Collaborator $configsMock;
    private ReflectionClass $mobileConfigMockFactory;

    public function let(
        MobileConfigReaderService $mobileConfigReaderServiceMock,
        Config $configsMock
    ) {
        $this->beConstructedWith($mobileConfigReaderServiceMock, $configsMock);
        $this->mobileConfigReaderServiceMock = $mobileConfigReaderServiceMock;
        $this->configsMock = $configsMock;

        $this->mobileConfigMockFactory = new ReflectionClass(MobileConfig::class);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(AndroidAssetLinksService::class);
    }

    public function it_should_return_android_asset_links_data(): void
    {
        $androidKeyStoreFingerprint = "androidKeystoreFingerprint";
        $appAndroidPackage = "com.minds.mobile";

        $this->mobileConfigReaderServiceMock->getMobileConfig()->willReturn(
            $this->generateMobileConfigMock($androidKeyStoreFingerprint, $appAndroidPackage)
        );

        $this->get()->shouldReturn([
            [
                "relation" => ["delegate_permission/common.handle_all_urls"],
                "target" => [
                    "namespace" => "android_app",
                    "package_name" => "com.minds.mobile",
                    "sha256_cert_fingerprints" => [$androidKeyStoreFingerprint]
                ]
            ]
        ]);
    }

    public function it_should_throw_an_error_when_no_android_keystore_fingerprint_is_set(): void
    {
        $androidKeyStoreFingerprint = null;

        $this->mobileConfigReaderServiceMock->getMobileConfig()->willReturn(
            $this->generateMobileConfigMock($androidKeyStoreFingerprint)
        );

        $this->shouldThrow(new ServerErrorException("Android keystore fingerprint is not set"))->during('get');
    }
    
    public function it_should_throw_an_error_when_no_android_package_is_set(): void
    {
        $androidKeyStoreFingerprint = "androidKeystoreFingerprint";
        $appAndroidPackage = null;

        $this->mobileConfigReaderServiceMock->getMobileConfig()->willReturn(
            $this->generateMobileConfigMock($androidKeyStoreFingerprint)
        );

        $this->shouldThrow(new ServerErrorException("iOS bundle ID is not set"))->during('get');
    }
    
    private function generateMobileConfigMock(string|null $fingerPrintValue = null, string|null $appAndroidPackageValue = null): MobileConfig
    {
        $mobileConfig = $this->mobileConfigMockFactory->newInstanceWithoutConstructor();
        $this->mobileConfigMockFactory->getProperty('androidKeystoreFingerprint')->setValue($mobileConfig, $fingerPrintValue);
        $this->mobileConfigMockFactory->getProperty('appAndroidPackage')->setValue($mobileConfig, $appAndroidPackageValue);
        return $mobileConfig;
    }
}
