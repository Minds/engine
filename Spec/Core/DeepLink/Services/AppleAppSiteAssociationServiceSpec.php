<?php

namespace Spec\Minds\Core\DeepLink\Services;

use Minds\Core\Config\Config;
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
        $this->shouldHaveType(AppleAppSiteAssociationService::class);
    }

    public function it_should_return_site_association_data_for_non_tenant(): void
    {
        $appleDevelopmentTeamId = "123";
        $appIosBundle = "com.minds.mobile";

        $this->mobileConfigReaderServiceMock->getMobileConfig()->willReturn(
            $this->generateMobileConfigMock($appleDevelopmentTeamId, $appIosBundle)
        );

        $this->configsMock->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(null);

        $this->get()->shouldReturn([
            'activitycontinuation' => [
                "apps" => [
                    "$appleDevelopmentTeamId.$appIosBundle",
                    "$appleDevelopmentTeamId.com.minds.chat"
                ]
            ],
            'applinks' => [
                'apps' => [],
                'details' => [
                    [
                        'appID' => "$appleDevelopmentTeamId.$appIosBundle",
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
                    "$appleDevelopmentTeamId.$appIosBundle",
                ]
            ],
        ]);
    }


    public function it_should_return_site_association_data_for_tenant(): void
    {
        $appleDevelopmentTeamId = "123";
        $appIosBundle = "com.tenant.mobile";

        $this->mobileConfigReaderServiceMock->getMobileConfig()->willReturn(
            $this->generateMobileConfigMock($appleDevelopmentTeamId, $appIosBundle)
        );

        $this->configsMock->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(123);

        $this->get()->shouldReturn([
            'activitycontinuation' => [
                    "apps" => [
                            "$appleDevelopmentTeamId.$appIosBundle",
                    ]
            ],
            'applinks' => [
                    'apps' => [],
                    'details' => [
                            [
                                    'appID' => "$appleDevelopmentTeamId.$appIosBundle",
                                    'paths' => [
                                            'NOT /api/*',
                                            'NOT /register',
                                            'NOT /login',
                                            '/*'
                                    ]
                            ]
                    ]
            ],
            'webcredentials' => [
                    'apps' => [
                            "$appleDevelopmentTeamId.$appIosBundle",
                    ]
            ],
        ]);
    }

    public function it_should_throw_an_error_when_no_apple_dev_team_id_is_set(): void
    {
        $appleDevelopmentTeamId = "123";
        $appIosBundle = null;

        $this->mobileConfigReaderServiceMock->getMobileConfig()->willReturn(
            $this->generateMobileConfigMock($appleDevelopmentTeamId)
        );

        $this->shouldThrow(new ServerErrorException("iOS bundle ID is not set"))->during('get');
    }

    public function it_should_throw_an_error_when_no_app_ios_bundle_is_set(): void
    {
        $appleDevelopmentTeamId = null;

        $this->mobileConfigReaderServiceMock->getMobileConfig()->willReturn(
            $this->generateMobileConfigMock($appleDevelopmentTeamId)
        );

        $this->shouldThrow(new ServerErrorException("Apple development team ID is not set"))->during('get');
    }
    
    private function generateMobileConfigMock(string|null $appleDevelopmentTeamId = null, string|null $appIosBundle = null): MobileConfig
    {
        $mobileConfig = $this->mobileConfigMockFactory->newInstanceWithoutConstructor();
        $this->mobileConfigMockFactory->getProperty('appleDevelopmentTeamId')->setValue($mobileConfig, $appleDevelopmentTeamId);
        $this->mobileConfigMockFactory->getProperty('appIosBundle')->setValue($mobileConfig, $appIosBundle);
        return $mobileConfig;
    }
}
