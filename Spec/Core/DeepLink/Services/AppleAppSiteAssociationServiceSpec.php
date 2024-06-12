<?php

namespace Spec\Minds\Core\DeepLink\Services;

use Minds\Core\DeepLink\Services\AppleAppSiteAssociationService;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigReaderService;
use Minds\Core\MultiTenant\MobileConfigs\Types\MobileConfig;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use ReflectionClass;

class AppleAppSiteAssociationServiceSpec extends ObjectBehavior
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
        $this->shouldHaveType(AppleAppSiteAssociationService::class);
    }

    public function it_should_return_site_association_data(): void
    {
        $appleDevelopmentTeamId = "123";

        $this->mobileConfigReaderServiceMock->getMobileConfig()->willReturn(
            $this->generateMobileConfigMock($appleDevelopmentTeamId)
        );

        $this->get()->shouldReturn([
            'activitycontinuation' => [
                    "apps" => [
                            "$appleDevelopmentTeamId.com.minds.mobile",
                            "$appleDevelopmentTeamId.com.minds.chat"
                    ]
            ],
            'applinks' => [
                    'apps' => [],
                    'details' => [
                            [
                                    'appID' => "$appleDevelopmentTeamId.com.minds.mobile",
                                    'paths' => [
                                            'NOT /api/*',
                                            'NOT /register',
                                            'NOT /login',
                                            '/*'
                                    ]
                            ],
                            [
                                    'appID' => "$appleDevelopmentTeamId.com.minds.chat",
                                    'paths' => ['/*']
                            ]
                    ]
            ],
            'webcredentials' => [
                    'apps' => [
                            "$appleDevelopmentTeamId.com.minds.mobile",
                    ]
            ],
        ]);
    }

    public function it_should_throw_an_error_when_no_android_keystore_fingerprint_is_set(): void
    {
        $appleDevelopmentTeamId = null;

        $this->mobileConfigReaderServiceMock->getMobileConfig()->willReturn(
            $this->generateMobileConfigMock($appleDevelopmentTeamId)
        );

        $this->shouldThrow(new ServerErrorException("Apple development team ID is not set"))->during('get');
    }
    
    private function generateMobileConfigMock(string|null $appleDevelopmentTeamId = null): MobileConfig
    {
        $mobileConfig = $this->mobileConfigMockFactory->newInstanceWithoutConstructor();
        $this->mobileConfigMockFactory->getProperty('appleDevelopmentTeamId')->setValue($mobileConfig, $appleDevelopmentTeamId);
        return $mobileConfig;
    }
}
