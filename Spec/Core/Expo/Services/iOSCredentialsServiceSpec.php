<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Expo\Services;

use Minds\Core\Config\Config;
use Minds\Core\Expo\Clients\ExpoGqlClient;
use Minds\Core\Expo\ExpoConfig;
use Minds\Core\Expo\Queries\Credentials\iOS\CreateAppleAppIdentifierQuery;
use Minds\Core\Expo\Queries\Credentials\iOS\CreateAppleDistributionCertificateQuery;
use Minds\Core\Expo\Queries\Credentials\iOS\CreateAppleProvisioningProfileQuery;
use Minds\Core\Expo\Queries\Credentials\iOS\CreateApplePushKeyQuery;
use Minds\Core\Expo\Queries\Credentials\iOS\CreateAscApiKeyQuery;
use Minds\Core\Expo\Queries\Credentials\iOS\CreateIosAppBuildCredentialsQuery;
use Minds\Core\Expo\Queries\Credentials\iOS\CreateIosAppCredentialsQuery;
use Minds\Core\Expo\Queries\Credentials\iOS\DeleteIosAppCredentialsQuery;
use Minds\Core\Expo\Queries\Credentials\iOS\GetAllAppleAppIdentifiersQuery;
use Minds\Core\Expo\Queries\Credentials\iOS\SetAscApiKeyForIosAppCredentialsQuery;
use Minds\Core\Expo\Queries\Credentials\iOS\SetPushKeyForIosAppCredentialsQuery;
use Minds\Core\Expo\Services\iOSCredentialsService;
use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Configs\Manager as MultiTenantConfigsManager;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class iOSCredentialsServiceSpec extends ObjectBehavior
{
    private Collaborator $expoGqlClient;
    private Collaborator $expoConfig;
    private Collaborator $config;
    private Collaborator $multiTenantDataService;
    private Collaborator $multiTenantConfigsManager;
    private Collaborator $getAllAppleAppIdentifiersQuery;
    private Collaborator $createAppleAppIdentifierQuery;
    private Collaborator $createAppleDistributionCertificateQuery;
    private Collaborator $createAppleProvisioningProfileQuery;
    private Collaborator $createIosAppCredentialsQuery;
    private Collaborator $createIosAppBuildCredentialsQuery;
    private Collaborator $createApplePushKeyQuery;
    private Collaborator $setPushKeyForIosAppCredentialsQuery;
    private Collaborator $createAscApiKeyQuery;
    private Collaborator $setAscApiKeyForIosAppCredentialsQuery;
    private Collaborator $deleteIosAppCredentialsQuery;

    public function let(
        ExpoGqlClient $expoGqlClient,
        ExpoConfig $expoConfig,
        Config $config,
        MultiTenantDataService $multiTenantDataService,
        MultiTenantConfigsManager $multiTenantConfigsManager,
        GetAllAppleAppIdentifiersQuery $getAllAppleAppIdentifiersQuery,
        CreateAppleAppIdentifierQuery $createAppleAppIdentifierQuery,
        CreateAppleDistributionCertificateQuery $createAppleDistributionCertificateQuery,
        CreateAppleProvisioningProfileQuery $createAppleProvisioningProfileQuery,
        CreateIosAppCredentialsQuery $createIosAppCredentialsQuery,
        CreateIosAppBuildCredentialsQuery $createIosAppBuildCredentialsQuery,
        CreateApplePushKeyQuery $createApplePushKeyQuery,
        SetPushKeyForIosAppCredentialsQuery $setPushKeyForIosAppCredentialsQuery,
        CreateAscApiKeyQuery $createAscApiKeyQuery,
        SetAscApiKeyForIosAppCredentialsQuery $setAscApiKeyForIosAppCredentialsQuery,
        DeleteIosAppCredentialsQuery $deleteIosAppCredentialsQuery,
    ) {
        $this->expoGqlClient = $expoGqlClient;
        $this->expoConfig = $expoConfig;
        $this->config = $config;
        $this->multiTenantDataService = $multiTenantDataService;
        $this->multiTenantConfigsManager = $multiTenantConfigsManager;
        $this->getAllAppleAppIdentifiersQuery = $getAllAppleAppIdentifiersQuery;
        $this->createAppleAppIdentifierQuery = $createAppleAppIdentifierQuery;
        $this->createAppleDistributionCertificateQuery = $createAppleDistributionCertificateQuery;
        $this->createAppleProvisioningProfileQuery = $createAppleProvisioningProfileQuery;
        $this->createIosAppCredentialsQuery = $createIosAppCredentialsQuery;
        $this->createIosAppBuildCredentialsQuery = $createIosAppBuildCredentialsQuery;
        $this->createApplePushKeyQuery = $createApplePushKeyQuery;
        $this->setPushKeyForIosAppCredentialsQuery = $setPushKeyForIosAppCredentialsQuery;
        $this->createAscApiKeyQuery = $createAscApiKeyQuery;
        $this->setAscApiKeyForIosAppCredentialsQuery = $setAscApiKeyForIosAppCredentialsQuery;
        $this->deleteIosAppCredentialsQuery = $deleteIosAppCredentialsQuery;
    
        $this->beConstructedWith(
            $expoGqlClient,
            $expoConfig,
            $config,
            $multiTenantDataService,
            $multiTenantConfigsManager,
            $getAllAppleAppIdentifiersQuery,
            $createAppleAppIdentifierQuery,
            $createAppleDistributionCertificateQuery,
            $createAppleProvisioningProfileQuery,
            $createIosAppCredentialsQuery,
            $createIosAppBuildCredentialsQuery,
            $createApplePushKeyQuery,
            $setPushKeyForIosAppCredentialsQuery,
            $createAscApiKeyQuery,
            $setAscApiKeyForIosAppCredentialsQuery,
            $deleteIosAppCredentialsQuery
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(iOSCredentialsService::class);
    }

    // setup project credentials

    public function it_should_set_up_full_project_credentials() {
        $tenantId = 1234567890123456;
        $projectId = '2234567890123456';
        $accountId = '31234567890123456';
        $accountName = 'accountName';
        $bundleIdentifier = 'com.example.app';
        $iosDistributionType = 'DEVELOPMENT';
        $distributionCertP12 = '~P12Cert~';
        $distributionCertPassword = 'password';
        $appleProvisioningProfile = '~ProvisioningProfile~';
        $pushKeyP8 = '~PushKeyP8~';
        $pushKeyIdentifier = 'push_identifier';
        $ascKeyP8 = '~AscKeyP8~';
        $ascKeyIdentifier = 'asc_identifier';
        $ascKeyIssuerIdentifier = 'asc_issuer_identifier';
        $ascName = 'asc_name';
        $tenantConfigs = new Tenant(
            id: $tenantId,
            config: new MultiTenantConfig(expoProjectId: $projectId)
        );
        $appleAppleIdentifierId = 'apple_app_identifier_id';
        $appleTeamId = 'apple_team_id';
        $distributionCertificateId = 'apple_distribution_certificate_id';
        $provisioningProfileId = 'apple_provisioning_profile_id';
        $pushKeyId = 'apple_push_key_id';
        $ascKeyId = 'apple_asc_api_key_id';
        $iosAppCredentialsId = 'ios_app_credentials_id';
        $iosAppBuildCredentialsId = 'ios_app_build_credentials_id';

        $this->expoConfig->accountId = $accountId;
        $this->expoConfig->accountName = $accountName;
        $this->expoConfig->appleTeamId = $appleTeamId;

        $this->config->get('tenant_id')->shouldBeCalled()->willReturn($tenantId);
        $this->multiTenantDataService->getTenantFromId($tenantId)
            ->shouldBeCalled()
            ->willReturn($tenantConfigs);

        // getOrCreateAppleAppIdentifier

        $this->getAllAppleAppIdentifiersQuery->build(
            accountName: $this->expoConfig->accountName
        )->shouldBeCalled()->willReturn(['query' => 'getAllAppleAppIdentifiersQuery']);

        $this->expoGqlClient->request(
            ['query' => 'getAllAppleAppIdentifiersQuery']
        )->shouldBeCalled()->willReturn([
            'data' => [
                'account' => [
                    'byName' => [
                        'appleAppIdentifiers' => [
                            [
                                'bundleIdentifier' => $bundleIdentifier,
                                'id' => $appleAppleIdentifierId
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    
        // batchPreAppCredentialCreationQueries

        $this->createAppleDistributionCertificateQuery->build(
            certPassword: $distributionCertPassword,
            certP12: $distributionCertP12,
            accountId: $accountId
        )->shouldBeCalled()->willReturn(['query' => 'createAppleDistributionCertificateQuery']);

        $this->createAppleProvisioningProfileQuery->build(
            appleProvisioningProfile: $appleProvisioningProfile,
            appleAppIdentifierId: $appleAppleIdentifierId,
            accountId: $accountId
        )->shouldBeCalled()->willReturn(['query' => 'createAppleProvisioningProfileQuery']);

        $this->createApplePushKeyQuery->build(
            keyIdentifier: $pushKeyIdentifier,
            keyP8: $pushKeyP8,
            appleTeamId: $appleTeamId,
            accountId: $accountId
        )->shouldBeCalled()->willReturn(['query' => 'createApplePushKeyQuery']);

        $this->createAscApiKeyQuery->build(
            keyIdentifier: $ascKeyIdentifier,
            keyP8: $ascKeyP8,
            issuerIdentifier: $ascKeyIssuerIdentifier,
            name: $ascName,
            accountId: $accountId
        )->shouldBeCalled()->willReturn(['query' => 'createAscApiKeyQuery']);

        $this->expoGqlClient->request([
            ['query' => 'createAppleDistributionCertificateQuery'],
            ['query' => 'createAppleProvisioningProfileQuery'],
            ['query' => 'createApplePushKeyQuery'],
            ['query' => 'createAscApiKeyQuery']
        ])->shouldBeCalled()->willReturn([
            [
                'data' => [
                    'createAppleDistributionCertificate' => [
                        'createAppleDistributionCertificate' => [
                            'id' => $distributionCertificateId
                        ]
                    ]
                ]
            ],
            [
                'data' => [
                    'createAppleProvisioningProfile' => [
                        'createAppleProvisioningProfile' => [
                            'id' => $provisioningProfileId
                        ]
                    ]
                ]
            ],
            [
                'data' => [
                    'createApplePushKey' => [
                        'createApplePushKey' => [
                            'id' => $pushKeyId
                        ]
                    ]
                ]
            ],
            [
                'data' => [
                    'createAppStoreConnectApiKey' => [
                        'createAppStoreConnectApiKey' => [
                            'id' => $ascKeyId
                        ]
                    ]
                ]
            ],
        ]);

        // createIosAppCredentials

        $this->createIosAppCredentialsQuery->build(
            appleAppIdentifierId: $appleAppleIdentifierId,
            appId: $projectId,
            pushKeyId: $pushKeyId,
            ascKeyId: $ascKeyId
        )->shouldBeCalled()->willReturn(['query' => 'createIosAppCredentialsQuery']);

        $this->expoGqlClient->request(['query' => 'createIosAppCredentialsQuery'])
            ->shouldBeCalled()->willReturn([
                'data' => [
                    'iosAppCredentials' => [
                        'createIosAppCredentials' => [
                            'id' => $iosAppCredentialsId
                        ]
                    ]
                ]
            ]);

        // createIosAppBuildCredentials

        $this->createIosAppBuildCredentialsQuery->build(
            iosDistributionType: $iosDistributionType,
            distributionCertificateId: $distributionCertificateId,
            provisioningProfileId: $provisioningProfileId,
            iosAppCredentialsId: $iosAppCredentialsId,
        )->shouldBeCalled()->willReturn(['query' => 'createIosAppBuildCredentialsQuery']);

        $this->expoGqlClient->request(['query' => 'createIosAppBuildCredentialsQuery'])
            ->shouldBeCalled()->willReturn([
                'data' => [
                    'iosAppBuildCredentials' => [
                        'createIosAppBuildCredentials' => [
                            'id' => $iosAppBuildCredentialsId
                        ]
                    ]
                ]
            ]);

        $this->multiTenantConfigsManager->upsertConfigs(
            null,
            null,
            null,
            null,
            null,
            null,
            $iosAppCredentialsId,
            null,
            $iosAppBuildCredentialsId
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setupProjectCredentials(
            bundleIdentifier: $bundleIdentifier,
            iosDistributionType: $iosDistributionType,
            distributionCertP12: $distributionCertP12,
            distributionCertPassword: $distributionCertPassword,
            appleProvisioningProfile: $appleProvisioningProfile,
            pushKeyP8: $pushKeyP8,
            pushKeyIdentifier: $pushKeyIdentifier,
            ascKeyP8: $ascKeyP8,
            ascKeyIdentifier: $ascKeyIdentifier,
            ascKeyIssuerIdentifier: $ascKeyIssuerIdentifier,
            ascName: $ascName
        )->shouldBe([
            'apple_app_identifier_id' => $appleAppleIdentifierId,
            'ios_app_credentials_id' => $iosAppCredentialsId,
            'ios_app_build_credentials_id' => $iosAppBuildCredentialsId,
            'asc_key_id' => $ascKeyId,
            'distribution_cert_id' => $distributionCertificateId,
            'provisioning_profile_id' => $provisioningProfileId,
            'push_key_id' => $pushKeyId
        ]);
    }

    public function it_should_set_up_partial_project_credentials() {
        $tenantId = 1234567890123456;
        $projectId = '2234567890123456';
        $accountId = '31234567890123456';
        $accountName = 'accountName';
        $bundleIdentifier = 'com.example.app';
        $iosDistributionType = 'DEVELOPMENT';
        $distributionCertP12 = '~P12Cert~';
        $distributionCertPassword = 'password';
        $appleProvisioningProfile = '~ProvisioningProfile~';
        $pushKeyP8 = null;
        $pushKeyIdentifier = null;
        $ascKeyP8 = null;
        $ascKeyIdentifier = null;
        $ascKeyIssuerIdentifier = null;
        $ascName = null;
        $tenantConfigs = new Tenant(
            id: $tenantId,
            config: new MultiTenantConfig(expoProjectId: $projectId)
        );
        $appleAppleIdentifierId = 'apple_app_identifier_id';
        $appleTeamId = 'apple_team_id';
        $distributionCertificateId = 'apple_distribution_certificate_id';
        $provisioningProfileId = 'apple_provisioning_profile_id';
        $pushKeyId = null;
        $ascKeyId = null;
        $iosAppCredentialsId = 'ios_app_credentials_id';
        $iosAppBuildCredentialsId = 'ios_app_build_credentials_id';

        $this->expoConfig->accountId = $accountId;
        $this->expoConfig->accountName = $accountName;
        $this->expoConfig->appleTeamId = $appleTeamId;

        $this->config->get('tenant_id')->shouldBeCalled()->willReturn($tenantId);
        $this->multiTenantDataService->getTenantFromId($tenantId)
            ->shouldBeCalled()
            ->willReturn($tenantConfigs);

        // getOrCreateAppleAppIdentifier

        $this->getAllAppleAppIdentifiersQuery->build(
            accountName: $this->expoConfig->accountName
        )->shouldBeCalled()->willReturn(['query' => 'getAllAppleAppIdentifiersQuery']);

        $this->expoGqlClient->request(
            ['query' => 'getAllAppleAppIdentifiersQuery']
        )->shouldBeCalled()->willReturn([
            'data' => [
                'account' => [
                    'byName' => [
                        'appleAppIdentifiers' => [
                            [
                                'bundleIdentifier' => $bundleIdentifier,
                                'id' => $appleAppleIdentifierId
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    
        // batchPreAppCredentialCreationQueries

        $this->createAppleDistributionCertificateQuery->build(
            certPassword: $distributionCertPassword,
            certP12: $distributionCertP12,
            accountId: $accountId
        )->shouldBeCalled()->willReturn(['query' => 'createAppleDistributionCertificateQuery']);

        $this->createAppleProvisioningProfileQuery->build(
            appleProvisioningProfile: $appleProvisioningProfile,
            appleAppIdentifierId: $appleAppleIdentifierId,
            accountId: $accountId
        )->shouldBeCalled()->willReturn(['query' => 'createAppleProvisioningProfileQuery']);

        $this->createApplePushKeyQuery->build(
            keyIdentifier: $pushKeyIdentifier,
            keyP8: $pushKeyP8,
            appleTeamId: $appleTeamId,
            accountId: $accountId
        )->shouldNotBeCalled();

        $this->createAscApiKeyQuery->build(
            keyIdentifier: $ascKeyIdentifier,
            keyP8: $ascKeyP8,
            issuerIdentifier: $ascKeyIssuerIdentifier,
            name: $ascName,
            accountId: $accountId
        )->shouldNotBeCalled();

        $this->expoGqlClient->request([
            ['query' => 'createAppleDistributionCertificateQuery'],
            ['query' => 'createAppleProvisioningProfileQuery']
        ])->shouldBeCalled()->willReturn([
            [
                'data' => [
                    'createAppleDistributionCertificate' => [
                        'createAppleDistributionCertificate' => [
                            'id' => $distributionCertificateId
                        ]
                    ]
                ]
            ],
            [
                'data' => [
                    'createAppleProvisioningProfile' => [
                        'createAppleProvisioningProfile' => [
                            'id' => $provisioningProfileId
                        ]
                    ]
                ]
            ]
        ]);

        // createIosAppCredentials

        $this->createIosAppCredentialsQuery->build(
            appleAppIdentifierId: $appleAppleIdentifierId,
            appId: $projectId,
            pushKeyId: null,
            ascKeyId: null
        )->shouldBeCalled()->willReturn(['query' => 'createIosAppCredentialsQuery']);

        $this->expoGqlClient->request(['query' => 'createIosAppCredentialsQuery'])
            ->shouldBeCalled()->willReturn([
                'data' => [
                    'iosAppCredentials' => [
                        'createIosAppCredentials' => [
                            'id' => $iosAppCredentialsId
                        ]
                    ]
                ]
            ]);

        // createIosAppBuildCredentials

        $this->createIosAppBuildCredentialsQuery->build(
            iosDistributionType: $iosDistributionType,
            distributionCertificateId: $distributionCertificateId,
            provisioningProfileId: $provisioningProfileId,
            iosAppCredentialsId: $iosAppCredentialsId,
        )->shouldBeCalled()->willReturn(['query' => 'createIosAppBuildCredentialsQuery']);

        $this->expoGqlClient->request(['query' => 'createIosAppBuildCredentialsQuery'])
            ->shouldBeCalled()->willReturn([
                'data' => [
                    'iosAppBuildCredentials' => [
                        'createIosAppBuildCredentials' => [
                            'id' => $iosAppBuildCredentialsId
                        ]
                    ]
                ]
            ]);

        $this->multiTenantConfigsManager->upsertConfigs(
            null,
            null,
            null,
            null,
            null,
            null,
            $iosAppCredentialsId,
            null,
            $iosAppBuildCredentialsId
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setupProjectCredentials(
            bundleIdentifier: $bundleIdentifier,
            iosDistributionType: $iosDistributionType,
            distributionCertP12: $distributionCertP12,
            distributionCertPassword: $distributionCertPassword,
            appleProvisioningProfile: $appleProvisioningProfile,
        )->shouldBe([
            'apple_app_identifier_id' => $appleAppleIdentifierId,
            'ios_app_credentials_id' => $iosAppCredentialsId,
            'ios_app_build_credentials_id' => $iosAppBuildCredentialsId,
            'asc_key_id' => $ascKeyId,
            'distribution_cert_id' => $distributionCertificateId,
            'provisioning_profile_id' => $provisioningProfileId,
            'push_key_id' => $pushKeyId
        ]);
    }

    // updateProjectCredentials

    public function it_should_update_project_credentials() {
        $tenantId = 1234567890123456;
        $projectId = '2234567890123456';
        $accountId = '31234567890123456';
        $accountName = 'accountName';
        $pushKeyP8 = '~PushKeyP8~';
        $pushKeyIdentifier = 'push_identifier';
        $ascKeyP8 = '~AscKeyP8~';
        $ascKeyIdentifier = 'asc_identifier';
        $ascKeyIssuerIdentifier = 'asc_issuer_identifier';
        $ascName = 'asc_name';
        $iosAppCredentialsId = 'ios_app_credentials_id';
        $tenantConfigs = new Tenant(
            id: $tenantId,
            config: new MultiTenantConfig(
                expoProjectId: $projectId,
                expoIosAppCredentialsId: $iosAppCredentialsId
            )
        );
        $appleTeamId = 'apple_team_id';
        $pushKeyId = 'apple_push_key_id';
        $ascKeyId = 'apple_asc_api_key_id';
        $iosAppCredentialsId = 'ios_app_credentials_id';

        $this->expoConfig->accountId = $accountId;
        $this->expoConfig->accountName = $accountName;
        $this->expoConfig->appleTeamId = $appleTeamId;

        $this->config->get('tenant_id')->shouldBeCalled()->willReturn($tenantId);
        $this->multiTenantDataService->getTenantFromId($tenantId)
            ->shouldBeCalled()
            ->willReturn($tenantConfigs);

        $this->createApplePushKeyQuery->build(
                keyIdentifier: $pushKeyIdentifier,
                keyP8: $pushKeyP8,
                appleTeamId: $appleTeamId,
                accountId: $accountId
        )->shouldBeCalled()->willReturn(['query' => 'createApplePushKeyQuery']);

        $this->createAscApiKeyQuery->build(
            keyIdentifier: $ascKeyIdentifier,
            keyP8: $ascKeyP8,
            issuerIdentifier: $ascKeyIssuerIdentifier,
            name: $ascName,
            accountId: $accountId
        )->shouldBeCalled()->willReturn(['query' => 'createAscApiKeyQuery']);
        
        $this->expoGqlClient->request([
            ['query' => 'createApplePushKeyQuery'],
            ['query' => 'createAscApiKeyQuery']
        ])->shouldBeCalled()->willReturn([
            [
                'data' => [
                    'createApplePushKey' => [
                        'createApplePushKey' => [
                            'id' => $pushKeyId
                        ]
                    ]
                ]
            ],
            [
                'data' => [
                    'createAppStoreConnectApiKey' => [
                        'createAppStoreConnectApiKey' => [
                            'id' => $ascKeyId
                        ]
                    ]
                ]
            ]
        ]);

        // batchAppCredentialUpdateApplyQueries

        $this->setPushKeyForIosAppCredentialsQuery->build(
            iosAppCredentialsId: $iosAppCredentialsId,
            pushKeyId: $pushKeyId
        )->shouldBeCalled()->willReturn(['query' => 'setPushKeyForIosAppCredentialsQuery']);
    

        $this->setAscApiKeyForIosAppCredentialsQuery->build(
            iosAppCredentialsId: $iosAppCredentialsId,
            ascApiKeyId: $ascKeyId
        )->shouldBeCalled()->willReturn(['query' => 'setAscApiKeyForIosAppCredentialsQuery']);
        
        $this->expoGqlClient->request([
            ['query' => 'setPushKeyForIosAppCredentialsQuery'],
            ['query' => 'setAscApiKeyForIosAppCredentialsQuery']
        ])->shouldBeCalled()->willReturn([
            [
                'data' => [
                    'applePushKey' => [
                        'applePushKey' => [
                            'id' => $pushKeyId
                        ]
                    ]
                ]
            ],
            [
                'data' => [
                    'appStoreConnectApiKey' => [
                        'appStoreConnectApiKey' => [
                            'id' => $ascKeyId
                        ]
                    ]
                ]
            ]
        ]);

        $this->updateProjectCredentials(
            pushKeyP8: $pushKeyP8,
            pushKeyIdentifier: $pushKeyIdentifier,
            ascKeyP8: $ascKeyP8,
            ascKeyIdentifier: $ascKeyIdentifier,
            ascKeyIssuerIdentifier: $ascKeyIssuerIdentifier,
            ascName: $ascName
        )->shouldBe([
            'ios_app_credentials_id' => $iosAppCredentialsId,
            'asc_key_id' => $ascKeyId,
            'push_key_id' => $pushKeyId
        ]);
    }

    // deleteProjectCredentials

    public function deleteProjectCredentials() {
        $tenantId = 1234567890123456;
        $projectId = '2234567890123456';
        $accountId = '31234567890123456';
        $iosAppCredentialsId = '1234567890123457';
        $tenantConfigs = new Tenant(
            id: $tenantId,
            config: new MultiTenantConfig(
                expoProjectId: $projectId,
                expoIosAppCredentialsId: $iosAppCredentialsId
            )
        );
        $this->expoConfig->accountId = $accountId;

        $this->config->get('tenant_id')->shouldBeCalled()->willReturn($tenantId);
        $this->multiTenantDataService->getTenantFromId($tenantId)
            ->shouldBeCalled()
            ->willReturn($tenantConfigs);

        $this->expoGqlClient->request($this->deleteIosAppCredentialsQuery->build(
            androidAppCredentialsId: $iosAppCredentialsId
        ))->shouldBeCalled()
            ->willReturn(['response' => true]);

        $this->deleteProjectCredentials()->shouldBe(true);
    }
}
