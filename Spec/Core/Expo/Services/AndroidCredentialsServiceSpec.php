<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Expo\Services;

use Minds\Core\Config\Config;
use Minds\Core\Expo\Clients\ExpoGqlClient;
use Minds\Core\Expo\ExpoConfig;
use Minds\Core\Expo\Queries\Credentials\Android\CreateAndroidAppBuildCredentialsQuery;
use Minds\Core\Expo\Queries\Credentials\Android\CreateAndroidAppCredentialsQuery;
use Minds\Core\Expo\Queries\Credentials\Android\CreateAndroidKeystoreQuery;
use Minds\Core\Expo\Queries\Credentials\Android\CreateFcmKeyQuery;
use Minds\Core\Expo\Queries\Credentials\Android\CreateGoogleServiceAccountKeyQuery;
use Minds\Core\Expo\Queries\Credentials\Android\DeleteAndroidAppCredentialsQuery;
use Minds\Core\Expo\Queries\Credentials\Android\SetFcmKeyOnAndroidAppCredentialsQuery;
use Minds\Core\Expo\Queries\Credentials\Android\SetGoogleServiceAccountKeyOnAndroidAppCredentialsQuery;
use Minds\Core\Expo\Services\AndroidCredentialsService;
use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Configs\Manager as MultiTenantConfigsManager;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class AndroidCredentialsServiceSpec extends ObjectBehavior
{
    private Collaborator $expoGqlClient;
    private Collaborator $expoConfig;
    private Collaborator $config;
    private Collaborator $multiTenantDataService;
    private Collaborator $multiTenantConfigsManager;
    private Collaborator $createAndroidKeystoreQuery;
    private Collaborator $createAndroidAppCredentialsQuery;
    private Collaborator $createAndroidAppBuildCredentialsQuery;
    private Collaborator $createGoogleServiceAccountKeyQuery;
    private Collaborator $setGoogleServiceAccountKeyOnAndroidAppCredentialsQuery;
    private Collaborator $createFcmKeyQuery;
    private Collaborator $setFcmKeyOnAndroidAppCredentialsQuery;
    private Collaborator $deleteAndroidAppCredentialsQuery;

    public function let(
        ExpoGqlClient $expoGqlClient,
        ExpoConfig $expoConfig,
        Config $config,
        MultiTenantDataService $multiTenantDataService,
        MultiTenantConfigsManager $multiTenantConfigsManager,
        CreateAndroidKeystoreQuery $createAndroidKeystoreQuery,
        CreateAndroidAppCredentialsQuery $createAndroidAppCredentialsQuery,
        CreateAndroidAppBuildCredentialsQuery $createAndroidAppBuildCredentialsQuery,
        CreateGoogleServiceAccountKeyQuery $createGoogleServiceAccountKeyQuery,
        SetGoogleServiceAccountKeyOnAndroidAppCredentialsQuery $setGoogleServiceAccountKeyOnAndroidAppCredentialsQuery,
        CreateFcmKeyQuery $createFcmKeyQuery,
        SetFcmKeyOnAndroidAppCredentialsQuery $setFcmKeyOnAndroidAppCredentialsQuery,
        DeleteAndroidAppCredentialsQuery $deleteAndroidAppCredentialsQuery,

    ) {
        $this->expoGqlClient = $expoGqlClient;
        $this->expoConfig = $expoConfig;
        $this->config = $config;
        $this->multiTenantDataService = $multiTenantDataService;
        $this->multiTenantConfigsManager = $multiTenantConfigsManager;
        $this->createAndroidKeystoreQuery = $createAndroidKeystoreQuery;
        $this->createAndroidAppCredentialsQuery = $createAndroidAppCredentialsQuery;
        $this->createAndroidAppBuildCredentialsQuery = $createAndroidAppBuildCredentialsQuery;
        $this->createGoogleServiceAccountKeyQuery = $createGoogleServiceAccountKeyQuery;
        $this->setGoogleServiceAccountKeyOnAndroidAppCredentialsQuery = $setGoogleServiceAccountKeyOnAndroidAppCredentialsQuery;
        $this->createFcmKeyQuery = $createFcmKeyQuery;
        $this->setFcmKeyOnAndroidAppCredentialsQuery = $setFcmKeyOnAndroidAppCredentialsQuery;
        $this->deleteAndroidAppCredentialsQuery = $deleteAndroidAppCredentialsQuery;

        $this->beConstructedWith(
            $expoGqlClient,
            $expoConfig,
            $config,
            $multiTenantDataService,
            $multiTenantConfigsManager,
            $createAndroidKeystoreQuery,
            $createAndroidAppCredentialsQuery,
            $createAndroidAppBuildCredentialsQuery,
            $createGoogleServiceAccountKeyQuery,
            $setGoogleServiceAccountKeyOnAndroidAppCredentialsQuery,
            $createFcmKeyQuery,
            $setFcmKeyOnAndroidAppCredentialsQuery,
            $deleteAndroidAppCredentialsQuery
        );
    }


    public function it_is_initializable()
    {
        $this->shouldHaveType(AndroidCredentialsService::class);
    }

    // setup project credentials

    public function it_should_set_up_full_project_credentials() {
        $tenantId = 1234567890123456;
        $projectId = '2234567890123456';
        $accountId = '31234567890123456';
        $applicationIdentifier = 'applicationIdentifier';
        $androidKeystorePassword = 'androidKeystorePassword';
        $androidKeystoreKeyAlias = 'androidKeystoreKeyAlias';
        $androidKeystoreKeyPassword = 'androidKeystoreKeyPassword';
        $androidBase64EncodedKeystore = base64_encode('androidBase64EncodedKeystore');
        $googleServiceAccountJson = json_encode(['id' => '4234567890123456']);
        $googleCloudMessagingToken = 'googleCloudMessagingToken';
        $tenantConfigs = new Tenant(
            id: $tenantId,
            config: new MultiTenantConfig(expoProjectId: $projectId)
        );
        $androidKeystoreId = '5234567890123456';
        $googleServiceAccountKeyId = '6234567890123456';
        $fcmKeyId = '7234567890123456';
        $androidAppCredentialsId = '8234567890123456';
        $androidAppBuildCredentialsId = '9234567890123456';

        $this->expoConfig->accountId = $accountId;

        $this->config->get('tenant_id')->shouldBeCalled()->willReturn($tenantId);
        $this->multiTenantDataService->getTenantFromId($tenantId)
            ->shouldBeCalled()
            ->willReturn($tenantConfigs);

        // batchPreAppCredentialCreationQueries

        $this->createAndroidKeystoreQuery->build(
            accountId: $this->expoConfig->accountId,
            androidKeystorePassword: $androidKeystorePassword,
            androidKeystoreKeyAlias: $androidKeystoreKeyAlias,
            androidKeystoreKeyPassword: $androidKeystoreKeyPassword,
            androidBase64EncodedKeystore: $androidBase64EncodedKeystore
        )->shouldBeCalled()->willReturn(['query1' => true]);

        $this->createGoogleServiceAccountKeyQuery->build(
            accountId: $this->expoConfig->accountId,
            googleServiceAccountCredentials: json_decode($googleServiceAccountJson, true)
        )->shouldBeCalled()->willReturn(['query2' => true]);

        $this->createFcmKeyQuery->build(
            accountId: $this->expoConfig->accountId,
            googleCloudMessagingToken: $googleCloudMessagingToken
        )->shouldBeCalled()->willReturn(['query3' => true]);

        $this->expoGqlClient->request(array (
            ['query1' => true],
            ['query2' => true],
            ['query3' => true]
          ))
            ->shouldBeCalled()
            ->willReturn(
                [
                    [
                        'data' => [
                            'createAndroidKeystore' => [
                                'createAndroidKeystore' => [
                                    'id' => $androidKeystoreId
                                ]
                            ]
                        ]
                    ],
                    [
                        'data' => [
                            'createGoogleServiceAccountKey' => [
                                'createGoogleServiceAccountKey' => [
                                    'id' => $googleServiceAccountKeyId
                                ]
                            ]
                        ]
                    ],
                    [
                        'data' => [
                            'createAndroidFcm' => [
                                'createAndroidFcm' => [
                                    'id' => $fcmKeyId
                                ]
                            ]
                        ]
                    ]
                ]
            );

        // createAndroidAppCredentials

        $this->createAndroidAppCredentialsQuery->build(
            projectId: $projectId,
            applicationIdentifier: $applicationIdentifier,
            fcmKeyId: $fcmKeyId,
            googleServiceAccountKeyId: $googleServiceAccountKeyId
        )->shouldBeCalled()->willReturn(['query4' => true]);

        $this->expoGqlClient->request(['query4' => true])
            ->shouldBeCalled()
            ->willReturn([
                'data' => [
                    'androidAppCredentials' => [
                        'createAndroidAppCredentials' => [
                            'id' => $androidAppCredentialsId
                        ]
                    ]
                ]
            ]);

        // createAndroidAppBuildCredentials

        $this->createAndroidAppBuildCredentialsQuery->build(
            androidAppCredentialsId: $androidAppCredentialsId,
            keystoreId: $androidKeystoreId,
            name: $applicationIdentifier
        )->shouldBeCalled()->willReturn(['query5' => true]);

        $this->expoGqlClient->request(['query5' => true])
            ->shouldBeCalled()
            ->willReturn([
                'data' => [
                    'androidAppBuildCredentials' => [
                        'createAndroidAppBuildCredentials' => [
                            'id' => $androidAppBuildCredentialsId
                        ]
                    ]
                ]
            ]);
         
        // update configs

        $this->multiTenantConfigsManager->upsertConfigs(
            null,
            null,
            null,
            null,
            null,
            $androidAppCredentialsId,
            null,
            $androidAppBuildCredentialsId,
            null
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setupProjectCredentials(
            applicationIdentifier: $applicationIdentifier,
            androidKeystorePassword: $androidKeystorePassword,
            androidKeystoreKeyAlias: $androidKeystoreKeyAlias,
            androidKeystoreKeyPassword: $androidKeystoreKeyPassword,
            androidBase64EncodedKeystore: $androidBase64EncodedKeystore,
            googleServiceAccountJson: $googleServiceAccountJson,
            googleCloudMessagingToken: $googleCloudMessagingToken
        )->shouldBe([
            'android_app_credentials_id' => $androidAppCredentialsId,
            'android_app_build_credentials_id' => $androidAppBuildCredentialsId,
            'keystore_id' => $androidKeystoreId,
            'google_service_account_key_id' => $googleServiceAccountKeyId,
            'fcm_key_id' => $fcmKeyId
        ]);
    }

    public function it_should_set_up_partial_project_credentials() {
        $tenantId = 1234567890123456;
        $projectId = '2234567890123456';
        $accountId = '31234567890123456';
        $applicationIdentifier = 'applicationIdentifier';
        $androidKeystorePassword = 'androidKeystorePassword';
        $androidKeystoreKeyAlias = 'androidKeystoreKeyAlias';
        $androidKeystoreKeyPassword = 'androidKeystoreKeyPassword';
        $androidBase64EncodedKeystore = base64_encode('androidBase64EncodedKeystore');
        $googleServiceAccountJson = null;
        $googleCloudMessagingToken = null;
        $tenantConfigs = new Tenant(
            id: $tenantId,
            config: new MultiTenantConfig(expoProjectId: $projectId)
        );
        $androidKeystoreId = '5234567890123456';
        $androidAppCredentialsId = '8234567890123456';
        $androidAppBuildCredentialsId = '9234567890123456';

        $this->expoConfig->accountId = $accountId;

        $this->config->get('tenant_id')->shouldBeCalled()->willReturn($tenantId);
        $this->multiTenantDataService->getTenantFromId($tenantId)
            ->shouldBeCalled()
            ->willReturn($tenantConfigs);

        // batchPreAppCredentialCreationQueries

        $this->createAndroidKeystoreQuery->build(
            accountId: $this->expoConfig->accountId,
            androidKeystorePassword: $androidKeystorePassword,
            androidKeystoreKeyAlias: $androidKeystoreKeyAlias,
            androidKeystoreKeyPassword: $androidKeystoreKeyPassword,
            androidBase64EncodedKeystore: $androidBase64EncodedKeystore
        )->shouldBeCalled()->willReturn(['query1' => true]);

        $this->createGoogleServiceAccountKeyQuery->build(
            Argument::any(), Argument::any()
        )->shouldNotBeCalled();

        $this->createFcmKeyQuery->build(
            accountId: $this->expoConfig->accountId,
            googleCloudMessagingToken: $googleCloudMessagingToken
        )->shouldNotBeCalled();

        $this->expoGqlClient->request(array (
            ['query1' => true]
          ))
            ->shouldBeCalled()
            ->willReturn(
                [
                    [
                        'data' => [
                            'createAndroidKeystore' => [
                                'createAndroidKeystore' => [
                                    'id' => $androidKeystoreId
                                ]
                            ]
                        ]
                    ]
                ]
            );

        // createAndroidAppCredentials

        $this->createAndroidAppCredentialsQuery->build(
            projectId: $projectId,
            applicationIdentifier: $applicationIdentifier,
            fcmKeyId: null,
            googleServiceAccountKeyId: null
        )->shouldBeCalled()->willReturn(['query4' => true]);

        $this->expoGqlClient->request(['query4' => true])
            ->shouldBeCalled()
            ->willReturn([
                'data' => [
                    'androidAppCredentials' => [
                        'createAndroidAppCredentials' => [
                            'id' => $androidAppCredentialsId
                        ]
                    ]
                ]
            ]);

        // createAndroidAppBuildCredentials

        $this->createAndroidAppBuildCredentialsQuery->build(
            androidAppCredentialsId: $androidAppCredentialsId,
            keystoreId: $androidKeystoreId,
            name: $applicationIdentifier
        )->shouldBeCalled()->willReturn(['query5' => true]);

        $this->expoGqlClient->request(['query5' => true])
            ->shouldBeCalled()
            ->willReturn([
                'data' => [
                    'androidAppBuildCredentials' => [
                        'createAndroidAppBuildCredentials' => [
                            'id' => $androidAppBuildCredentialsId
                        ]
                    ]
                ]
            ]);
         
        // update configs

        $this->multiTenantConfigsManager->upsertConfigs(
            null,
            null,
            null,
            null,
            null,
            $androidAppCredentialsId,
            null,
            $androidAppBuildCredentialsId,
            null
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setupProjectCredentials(
            applicationIdentifier: $applicationIdentifier,
            androidKeystorePassword: $androidKeystorePassword,
            androidKeystoreKeyAlias: $androidKeystoreKeyAlias,
            androidKeystoreKeyPassword: $androidKeystoreKeyPassword,
            androidBase64EncodedKeystore: $androidBase64EncodedKeystore,
            googleServiceAccountJson: $googleServiceAccountJson,
            googleCloudMessagingToken: $googleCloudMessagingToken
        )->shouldBe([
            'android_app_credentials_id' => $androidAppCredentialsId,
            'android_app_build_credentials_id' => $androidAppBuildCredentialsId,
            'keystore_id' => $androidKeystoreId,
            'google_service_account_key_id' => null,
            'fcm_key_id' => null
        ]);
    }

    // updateProjectCredentials

    public function it_should_update_project_credentials() {
        $tenantId = 1234567890123456;
        $projectId = '2234567890123456';
        $accountId = '31234567890123456';
        $applicationIdentifier = 'applicationIdentifier';
        $androidKeystorePassword = 'androidKeystorePassword';
        $androidKeystoreKeyAlias = 'androidKeystoreKeyAlias';
        $androidKeystoreKeyPassword = 'androidKeystoreKeyPassword';
        $androidBase64EncodedKeystore = base64_encode('androidBase64EncodedKeystore');
        $googleServiceAccountJson = json_encode(['id' => '4234567890123456']);
        $googleCloudMessagingToken = 'googleCloudMessagingToken';
        $androidKeystoreId = '5234567890123456';
        $googleServiceAccountKeyId = '6234567890123456';
        $fcmKeyId = '7234567890123456';
        $androidAppBuildCredentialsId = '9234567890123456';
        $expoAndroidAppCredentialsId = '1234567890123457';
        $tenantConfigs = new Tenant(
            id: $tenantId,
            config: new MultiTenantConfig(
                expoProjectId: $projectId,
                expoAndroidAppCredentialsId: $expoAndroidAppCredentialsId
            )
        );
        $this->expoConfig->accountId = $accountId;

        $this->config->get('tenant_id')->shouldBeCalled()->willReturn($tenantId);
        $this->multiTenantDataService->getTenantFromId($tenantId)
            ->shouldBeCalled()
            ->willReturn($tenantConfigs);

        // batchUpdateCreationQueries

        $this->createGoogleServiceAccountKeyQuery->build(
            accountId: $this->expoConfig->accountId,
            googleServiceAccountCredentials: json_decode($googleServiceAccountJson, true)
        )
            ->shouldBeCalled()
            ->willReturn(['query1' => true]);
        

        $this->createFcmKeyQuery->build(
            accountId: $this->expoConfig->accountId,
            googleCloudMessagingToken: $googleCloudMessagingToken
        )
            ->shouldBeCalled()
            ->willReturn(['query2' => true]);
     
        $this->expoGqlClient->request(array (
            ['query1' => true],
            ['query2' => true]
        ))
            ->shouldBeCalled()
            ->willReturn(
                [
                    [
                        'data' => [
                            'createGoogleServiceAccountKey' => [
                                'createGoogleServiceAccountKey' => [
                                    'id' => $googleServiceAccountKeyId
                                ]
                            ]
                        ]
                    ],
                    [
                        'data' => [
                            'createAndroidFcm' => [
                                'createAndroidFcm' => [
                                    'id' => $fcmKeyId
                                ]
                            ]
                        ]
                    ]
                ]
            );

        $this->setFcmKeyOnAndroidAppCredentialsQuery->build(
            androidAppCredentialsId: $expoAndroidAppCredentialsId,
            fcmKeyId: $fcmKeyId
        )
            ->shouldBeCalled()
            ->willReturn(['query3' => true]);

        $this->setGoogleServiceAccountKeyOnAndroidAppCredentialsQuery->build(
            androidAppCredentialsId: $expoAndroidAppCredentialsId,
            googleServiceAccountKeyId: $googleServiceAccountKeyId
        )
            ->shouldBeCalled()
            ->willReturn(['query4' => true]);

        $this->expoGqlClient->request([
            ['query3' => true],
            ['query4' => true]
        ])->shouldBeCalled()
            ->willReturn(
                [
                    [
                        'data' => [
                            'update' => [
                                'update' => [
                                    'id' => $googleServiceAccountKeyId
                                ]
                            ]
                        ]
                    ],
                    [
                        'data' => [
                            'update' => [
                                'update' => [
                                    'id' => $fcmKeyId
                                ]
                            ]
                        ]
                    ]
                ]
            );

        $this->updateProjectCredentials($googleServiceAccountJson, $googleCloudMessagingToken)
            ->shouldBe([
                'android_app_credentials_id' => $expoAndroidAppCredentialsId,
                'google_service_account_key_id' => $googleServiceAccountKeyId,
                'fcm_key_id' => $fcmKeyId,
            ]);
    }

    public function deleteProjectCredentials() {
        $tenantId = 1234567890123456;
        $projectId = '2234567890123456';
        $accountId = '31234567890123456';
        $expoAndroidAppCredentialsId = '1234567890123457';
        $tenantConfigs = new Tenant(
            id: $tenantId,
            config: new MultiTenantConfig(
                expoProjectId: $projectId,
                expoAndroidAppCredentialsId: $expoAndroidAppCredentialsId
            )
        );
        $this->expoConfig->accountId = $accountId;

        $this->config->get('tenant_id')->shouldBeCalled()->willReturn($tenantId);
        $this->multiTenantDataService->getTenantFromId($tenantId)
            ->shouldBeCalled()
            ->willReturn($tenantConfigs);

        $this->expoGqlClient->request($this->deleteAndroidAppCredentialsQuery->build(
            androidAppCredentialsId: $expoAndroidAppCredentialsId
        ))->shouldBeCalled()
            ->willReturn(['response' => true]);

        $this->deleteProjectCredentials()->shouldBe(true);
    }
}
