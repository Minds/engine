<?php
declare(strict_types=1);

namespace Minds\Core\Expo;

use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Expo\Services\AndroidCredentialsService;
use \GuzzleHttp\Client as GuzzleClient;
use Minds\Core\Config;
use Minds\Core\Expo\Clients\ExpoGqlClient;
use Minds\Core\Expo\Clients\ExpoHttpClient;
use Minds\Core\Expo\Controllers\AndroidCredentialsController;
use Minds\Core\Expo\Controllers\iOSCredentialsController;
use Minds\Core\Expo\Controllers\ProjectsController;
use Minds\Core\Expo\Queries\Credentials\Android\CreateAndroidAppBuildCredentialsQuery;
use Minds\Core\Expo\Queries\Credentials\Android\CreateAndroidAppCredentialsQuery;
use Minds\Core\Expo\Queries\Credentials\Android\CreateAndroidKeystoreQuery;
use Minds\Core\Expo\Queries\Credentials\iOS\CreateAppleAppIdentifierQuery;
use Minds\Core\Expo\Queries\Credentials\iOS\CreateAppleDistributionCertificateQuery;
use Minds\Core\Expo\Queries\Credentials\iOS\CreateAppleProvisioningProfileQuery;
use Minds\Core\Expo\Queries\Credentials\Android\CreateFcmKeyQuery;
use Minds\Core\Expo\Queries\Credentials\Android\CreateGoogleServiceAccountKeyQuery;
use Minds\Core\Expo\Queries\Credentials\Android\DeleteAndroidAppCredentialsQuery;
use Minds\Core\Expo\Queries\Credentials\iOS\CreateIosAppBuildCredentialsQuery;
use Minds\Core\Expo\Queries\Credentials\iOS\CreateIosAppCredentialsQuery;
use Minds\Core\Expo\Queries\Credentials\Android\SetFcmKeyOnAndroidAppCredentialsQuery;
use Minds\Core\Expo\Queries\Credentials\Android\SetGoogleServiceAccountKeyOnAndroidAppCredentialsQuery;
use Minds\Core\Expo\Queries\Credentials\iOS\CreateApplePushKeyQuery;
use Minds\Core\Expo\Queries\Credentials\iOS\CreateAscApiKeyQuery;
use Minds\Core\Expo\Queries\Credentials\iOS\DeleteIosAppCredentialsQuery;
use Minds\Core\Expo\Queries\Credentials\iOS\GetAllAppleAppIdentifiersQuery;
use Minds\Core\Expo\Queries\Credentials\iOS\SetAscApiKeyForIosAppCredentialsQuery;
use Minds\Core\Expo\Queries\Credentials\iOS\SetPushKeyForIosAppCredentialsQuery;
use Minds\Core\Expo\Services\iOSCredentialsService;
use Minds\Core\Expo\Services\ProjectsService;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws \Minds\Core\Di\ImmutableException
     */
    public function register(): void
    {
        // Clients

        $this->di->bind(ExpoGqlClient::class, function (Di $di): ExpoGqlClient {
            return new ExpoGqlClient(
                guzzleClient: $di->get(GuzzleClient::class),
                config: $di->get(ExpoConfig::class),
                logger: $di->get('Logger')
            );
        });

        $this->di->bind(ExpoHttpClient::class, function (Di $di): ExpoHttpClient {
            return new ExpoHttpClient(
                guzzleClient: $di->get(GuzzleClient::class),
                expoConfig: $di->get(ExpoConfig::class),
                config: $di->get(Config::class),
                logger: $di->get('Logger')
            );
        });

        // Config

        $this->di->bind(ExpoConfig::class, function (Di $di): ExpoConfig {
            return new ExpoConfig(
                config: $di->get('Config')
            );
        });

        // Services

        $this->di->bind(AndroidCredentialsService::class, function (Di $di): AndroidCredentialsService {
            return new AndroidCredentialsService(
                expoGqlClient: $di->get(ExpoGqlClient::class),
                config: $di->get(ExpoConfig::class),
                createAndroidKeystoreQuery: $di->get(CreateAndroidKeystoreQuery::class),
                createAndroidAppCredentialsQuery: $di->get(CreateAndroidAppCredentialsQuery::class),
                createAndroidAppBuildCredentialsQuery: $di->get(CreateAndroidAppBuildCredentialsQuery::class),
                createGoogleServiceAccountKeyQuery: $di->get(CreateGoogleServiceAccountKeyQuery::class),
                setGoogleServiceAccountKeyOnAndroidAppCredentialsQuery: $di->get(SetGoogleServiceAccountKeyOnAndroidAppCredentialsQuery::class),
                createFcmKeyQuery: $di->get(CreateFcmKeyQuery::class),
                setFcmKeyOnAndroidAppCredentialsQuery: $di->get(SetFcmKeyOnAndroidAppCredentialsQuery::class),
                deleteAndroidAppCredentialsQuery: $di->get(DeleteAndroidAppCredentialsQuery::class)
            );
        });

        $this->di->bind(iOSCredentialsService::class, function (Di $di): iOSCredentialsService {
            return new iOSCredentialsService(
                expoGqlClient: $di->get(ExpoGqlClient::class),
                config: $di->get(ExpoConfig::class),
                getAllAppleAppIdentifiersQuery: $di->get(GetAllAppleAppIdentifiersQuery::class),
                createAppleAppIdentifierQuery: $di->get(CreateAppleAppIdentifierQuery::class),
                createAppleDistributionCertificateQuery: $di->get(CreateAppleDistributionCertificateQuery::class),
                createAppleProvisioningProfileQuery: $di->get(CreateAppleProvisioningProfileQuery::class),
                createIosAppBuildCredentialsQuery: $di->get(CreateIosAppBuildCredentialsQuery::class),
                createIosAppCredentialsQuery: $di->get(CreateIosAppCredentialsQuery::class),
                createApplePushKeyQuery: $di->get(CreateApplePushKeyQuery::class),
                setPushKeyForIosAppCredentialsQuery: $di->get(SetPushKeyForIosAppCredentialsQuery::class),
                createAscApiKeyQuery: $di->get(CreateAscApiKeyQuery::class),
                setAscApiKeyForIosAppCredentialsQuery: $di->get(SetAscApiKeyForIosAppCredentialsQuery::class),
                deleteIosAppCredentialsQuery: $di->get(DeleteIosAppCredentialsQuery::class),
            );
        });

        $this->di->bind(ProjectsService::class, function (Di $di): ProjectsService {
            return new ProjectsService(
                expoHttpClient: $di->get(ExpoHttpClient::class),
                config: $di->get(ExpoConfig::class)
            );
        });

        // Controllers

        $this->di->bind(AndroidCredentialsController::class, function (Di $di): AndroidCredentialsController {
            return new AndroidCredentialsController(
                androidCredentialsService: $di->get(AndroidCredentialsService::class)
            );
        });


        $this->di->bind(iOSCredentialsController::class, function (Di $di): iOSCredentialsController {
            return new iOSCredentialsController(
                iosCredentialsService: $di->get(iosCredentialsService::class)
            );
        });

        $this->di->bind(ProjectsController::class, function (Di $di): ProjectsController {
            return new ProjectsController(
                projectsService: $di->get(ProjectsService::class)
            );
        });

        // Android queries

        $this->di->bind(CreateAndroidKeystoreQuery::class, function (Di $di): CreateAndroidKeystoreQuery {
            return new CreateAndroidKeystoreQuery();
        });

        $this->di->bind(CreateAndroidAppCredentialsQuery::class, function (Di $di): CreateAndroidAppCredentialsQuery {
            return new CreateAndroidAppCredentialsQuery();
        });

        $this->di->bind(CreateAndroidAppBuildCredentialsQuery::class, function (Di $di): CreateAndroidAppBuildCredentialsQuery {
            return new CreateAndroidAppBuildCredentialsQuery();
        });

        $this->di->bind(CreateGoogleServiceAccountKeyQuery::class, function (Di $di): CreateGoogleServiceAccountKeyQuery {
            return new CreateGoogleServiceAccountKeyQuery();
        });

        $this->di->bind(SetGoogleServiceAccountKeyOnAndroidAppCredentialsQuery::class, function (Di $di): SetGoogleServiceAccountKeyOnAndroidAppCredentialsQuery {
            return new SetGoogleServiceAccountKeyOnAndroidAppCredentialsQuery();
        });

        $this->di->bind(CreateFcmKeyQuery::class, function (Di $di): CreateFcmKeyQuery {
            return new CreateFcmKeyQuery();
        });

        $this->di->bind(SetFcmKeyOnAndroidAppCredentialsQuery::class, function (Di $di): SetFcmKeyOnAndroidAppCredentialsQuery {
            return new SetFcmKeyOnAndroidAppCredentialsQuery();
        });

        $this->di->bind(DeleteAndroidAppCredentialsQuery::class, function (Di $di): DeleteAndroidAppCredentialsQuery {
            return new DeleteAndroidAppCredentialsQuery();
        });

        // iOS Queries

        $this->di->bind(CreateAppleAppIdentifierQuery::class, function (Di $di): CreateAppleAppIdentifierQuery {
            return new CreateAppleAppIdentifierQuery();
        });

        $this->di->bind(CreateAppleDistributionCertificateQuery::class, function (Di $di): CreateAppleDistributionCertificateQuery {
            return new CreateAppleDistributionCertificateQuery();
        });

        $this->di->bind(CreateAppleProvisioningProfileQuery::class, function (Di $di): CreateAppleProvisioningProfileQuery {
            return new CreateAppleProvisioningProfileQuery();
        });

        $this->di->bind(CreateIosAppBuildCredentialsQuery::class, function (Di $di): CreateIosAppBuildCredentialsQuery {
            return new CreateIosAppBuildCredentialsQuery();
        });

        $this->di->bind(CreateIosAppCredentialsQuery::class, function (Di $di): CreateIosAppCredentialsQuery {
            return new CreateIosAppCredentialsQuery();
        });

        $this->di->bind(GetAllAppleAppIdentifiersQuery::class, function (Di $di): GetAllAppleAppIdentifiersQuery {
            return new GetAllAppleAppIdentifiersQuery();
        });

        $this->di->bind(CreateApplePushKeyQuery::class, function (Di $di): CreateApplePushKeyQuery {
            return new CreateApplePushKeyQuery();
        });

        $this->di->bind(SetPushKeyForIosAppCredentialsQuery::class, function (Di $di): SetPushKeyForIosAppCredentialsQuery {
            return new SetPushKeyForIosAppCredentialsQuery();
        });

        $this->di->bind(CreateAscApiKeyQuery::class, function (Di $di): CreateAscApiKeyQuery {
            return new CreateAscApiKeyQuery();
        });

        $this->di->bind(SetAscApiKeyForIosAppCredentialsQuery::class, function (Di $di): SetAscApiKeyForIosAppCredentialsQuery {
            return new SetAscApiKeyForIosAppCredentialsQuery();
        });

        $this->di->bind(DeleteIosAppCredentialsQuery::class, function (Di $di): DeleteIosAppCredentialsQuery {
            return new DeleteIosAppCredentialsQuery();
        });
    }
}
