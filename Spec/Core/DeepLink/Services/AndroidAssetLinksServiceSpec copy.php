<?php

namespace Spec\Minds\Core\DeepLink\Services;

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
    private ReflectionClass $mobileConfigMockFactory;

    public function let(
        MobileConfigReaderService $mobileConfigReaderServiceMock
    ) {
        $this->beConstructedWith($mobileConfigReaderServiceMock);
        $this->mobileConfigReaderServiceMock = $mobileConfigReaderServiceMock;

        $this->mobileConfigMockFactory = new ReflectionClass(MobileConfig::class);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(AndroidAssetLinksService::class);
    }

    public function it_should_return_android_asset_links_data(): void
    {
        $androidKeyStoreFingerprint = "androidKeystoreFingerprint";

        $this->mobileConfigReaderServiceMock->getMobileConfig()->willReturn(
            $this->generateMobileConfigMock($androidKeyStoreFingerprint)
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
    
    private function generateMobileConfigMock(string|null $fingerPrintValue = null): MobileConfig
    {
        $mobileConfig = $this->mobileConfigMockFactory->newInstanceWithoutConstructor();
        $this->mobileConfigMockFactory->getProperty('androidKeystoreFingerprint')->setValue($mobileConfig, $fingerPrintValue);
        return $mobileConfig;
    }
}
