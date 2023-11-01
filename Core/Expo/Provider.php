<?php
declare(strict_types=1);

namespace Minds\Core\Expo;

use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Expo\Services\AndroidCredentialsService;
use \GuzzleHttp\Client as GuzzleClient;
use Minds\Core\Expo\Controllers\AndroidController;
use Minds\Core\Expo\Controllers\iOSController;
use Minds\Core\Expo\Queries\Android\Credentials\CreateAndroidAppBuildCredentialsQuery;
use Minds\Core\Expo\Queries\Android\Credentials\CreateAndroidAppCredentialsQuery;
use Minds\Core\Expo\Queries\Android\Credentials\CreateAndroidKeystoreQuery;
use Minds\Core\Expo\Queries\iOS\Credentials\CreateAppleAppIdentifierQuery;
use Minds\Core\Expo\Queries\iOS\Credentials\CreateAppleDistributionCertificateQuery;
use Minds\Core\Expo\Queries\iOS\Credentials\CreateAppleProvisioningProfileQuery;
use Minds\Core\Expo\Queries\Android\Credentials\CreateFcmKeyQuery;
use Minds\Core\Expo\Queries\Android\Credentials\CreateGoogleServiceAccountKeyQuery;
use Minds\Core\Expo\Queries\Android\Credentials\DeleteAndroidAppCredentialsQuery;
use Minds\Core\Expo\Queries\iOS\Credentials\CreateIosAppBuildCredentialsQuery;
use Minds\Core\Expo\Queries\iOS\Credentials\CreateIosAppCredentialsQuery;
use Minds\Core\Expo\Queries\Android\Credentials\SetFcmKeyOnAndroidAppCredentialsQuery;
use Minds\Core\Expo\Queries\Android\Credentials\SetGoogleServiceAccountKeyOnAndroidAppCredentialsQuery;
use Minds\Core\Expo\Queries\iOS\Credentials\CreateApplePushKeyQuery;
use Minds\Core\Expo\Queries\iOS\Credentials\CreateAscApiKeyQuery;
use Minds\Core\Expo\Queries\iOS\Credentials\DeleteIosAppCredentialsQuery;
use Minds\Core\Expo\Queries\iOS\Credentials\GetAllAppleAppIdentifiersQuery;
use Minds\Core\Expo\Queries\iOS\Credentials\SetAscApiKeyForIosAppCredentialsQuery;
use Minds\Core\Expo\Queries\iOS\Credentials\SetPushKeyForIosAppCredentialsQuery;
use Minds\Core\Expo\Services\iOSCredentialsService;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws \Minds\Core\Di\ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(ExpoClient::class, function (Di $di): ExpoClient {
            return new ExpoClient(
                guzzleClient: $di->get(GuzzleClient::class),
                config: $di->get(ExpoConfig::class),
                logger: $di->get('Logger')
            );
        });

        $this->di->bind(AndroidCredentialsService::class, function (Di $di): AndroidCredentialsService {
            return new AndroidCredentialsService(
                expoClient: $di->get(ExpoClient::class),
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
                expoClient: $di->get(ExpoClient::class),
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

        $this->di->bind(ExpoConfig::class, function (Di $di): ExpoConfig {
            return new ExpoConfig(
                config: $di->get('Config')
            );
        });

        $this->di->bind(AndroidController::class, function (Di $di): AndroidController {
            return new AndroidController(
                androidCredentialsService: $di->get(AndroidCredentialsService::class)
            );
        });


        $this->di->bind(iOSController::class, function (Di $di): iOSController {
            return new iOSController(
                iosCredentialsService: $di->get(iosCredentialsService::class)
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
