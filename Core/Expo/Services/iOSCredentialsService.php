<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Services;

use Minds\Core\Expo\ExpoClient;
use Minds\Core\Expo\ExpoConfig;
use Minds\Core\Expo\Queries\iOS\Credentials\CreateAppleAppIdentifierQuery;
use Minds\Core\Expo\Queries\iOS\Credentials\CreateAppleDistributionCertificateQuery;
use Minds\Core\Expo\Queries\iOS\Credentials\CreateAppleProvisioningProfileQuery;
use Minds\Core\Expo\Queries\iOS\Credentials\CreateApplePushKeyQuery;
use Minds\Core\Expo\Queries\iOS\Credentials\CreateAscApiKeyQuery;
use Minds\Core\Expo\Queries\iOS\Credentials\CreateIosAppBuildCredentialsQuery;
use Minds\Core\Expo\Queries\iOS\Credentials\CreateIosAppCredentialsQuery;
use Minds\Core\Expo\Queries\iOS\Credentials\DeleteIosAppCredentialsQuery;
use Minds\Core\Expo\Queries\iOS\Credentials\GetAllAppleAppIdentifiersQuery;
use Minds\Core\Expo\Queries\iOS\Credentials\SetAscApiKeyForIosAppCredentialsQuery;
use Minds\Core\Expo\Queries\iOS\Credentials\SetPushKeyForIosAppCredentialsQuery;
use Minds\Exceptions\ServerErrorException;

/**
 * Service for managing iOS credentials in Expo.
 */
class iOSCredentialsService
{
    public function __construct(
        private ExpoClient $expoClient,
        private ExpoConfig $config,
        private GetAllAppleAppIdentifiersQuery $getAllAppleAppIdentifiersQuery,
        private CreateAppleAppIdentifierQuery $createAppleAppIdentifierQuery,
        private CreateAppleDistributionCertificateQuery $createAppleDistributionCertificateQuery,
        private CreateAppleProvisioningProfileQuery $createAppleProvisioningProfileQuery,
        private CreateIosAppCredentialsQuery $createIosAppCredentialsQuery,
        private CreateIosAppBuildCredentialsQuery $createIosAppBuildCredentialsQuery,
        private CreateApplePushKeyQuery $createApplePushKeyQuery,
        private SetPushKeyForIosAppCredentialsQuery $setPushKeyForIosAppCredentialsQuery,
        private CreateAscApiKeyQuery $createAscApiKeyQuery,
        private SetAscApiKeyForIosAppCredentialsQuery $setAscApiKeyForIosAppCredentialsQuery,
        private DeleteIosAppCredentialsQuery $deleteIosAppCredentialsQuery
    ) {
    }

    /**
     * Sets up the iOS credentials for a project.
     * @param string $bundleIdentifier - the bundle identifier for the app.
     * @param string $iosDistributionType - the type of distribution to use for the app. e.g. DEVELOPMENT.
     * @param string $distributionCertP12 - the distribution certificate in p12 format.
     * @param string $distributionCertPassword - the password for the distribution certificate.
     * @param string $appleProvisioningProfile - the provisioning profile for the app.
     * @param string $pushKeyP8 - the push key in p8 format.
     * @param string $pushKeyIdentifier - the identifier for the push key.
     * @param string $ascKeyP8 - the ASC API key in p8 format.
     * @param string $ascKeyIdentifier - the identifier for the ASC API key.
     * @param string $ascKeyIssuerIdentifier - the issuer identifier for the ASC API key.
     * @param string $ascName - the name for the ASC API key.
     * @throws ServerErrorException - on failure to create credentials.
     * @return array - the ids of the created credentials.
     */
    public function setupProjectCredentials(
        string $bundleIdentifier,
        string $iosDistributionType,
        string $distributionCertP12,
        string $distributionCertPassword,
        string $appleProvisioningProfile,
        string $pushKeyP8,
        string $pushKeyIdentifier,
        string $ascKeyP8,
        string $ascKeyIdentifier,
        string $ascKeyIssuerIdentifier,
        string $ascName
    ): array {
        $accountId = $this->config->accountId;
        $projectId = $this->config->projectId;

        $appleAppIdentifierId = $this->getOrCreateAppleAppIdentifier(
            bundleIdentifier: $bundleIdentifier,
            accountId: $accountId
        );

        $batchPreAppCredentialCreationQueriesResponse = $this->batchPreAppCredentialCreationQueries(
            distributionCertP12: $distributionCertP12,
            distributionCertPassword: $distributionCertPassword,
            appleProvisioningProfile: $appleProvisioningProfile,
            pushKeyP8: $pushKeyP8,
            pushKeyIdentifier: $pushKeyIdentifier,
            ascKeyP8: $ascKeyP8,
            ascKeyIdentifier: $ascKeyIdentifier,
            ascKeyIssuerIdentifier: $ascKeyIssuerIdentifier,
            ascName: $ascName,
            accountId: $this->config->accountId,
            appleAppIdentifierId: $appleAppIdentifierId
        );

        $distributionCertId = $batchPreAppCredentialCreationQueriesResponse['createAppleDistributionCertificate']['id'] ?? throw new ServerErrorException('Failed to create distribution certificate');
        $provisioningProfileId = $batchPreAppCredentialCreationQueriesResponse['createAppleProvisioningProfile']['id'] ?? throw new ServerErrorException('Failed to create distribution certificate');
        $pushKeyId = $batchPreAppCredentialCreationQueriesResponse['createApplePushKey']['id'] ?? throw new ServerErrorException('Failed to create apple push key');
        $ascKeyId = $batchPreAppCredentialCreationQueriesResponse['createAppStoreConnectApiKey']['id'] ?? throw new ServerErrorException('Failed to create ASC API key');

        $createIosAppCredentialsResponse = $this->createIosAppCredentials(
            appleAppIdentifierId: $appleAppIdentifierId,
            appId: $projectId,
            pushKeyId: $pushKeyId,
            ascKeyId: $ascKeyId
        );

        if (!$createIosAppCredentialsResponse || !$iosAppCredentialsId = $createIosAppCredentialsResponse['id']) {
            throw new ServerErrorException('Failed to create iOS app credentials');
        }

        $createIosAppBuildCredentialsResponse = $this->createIosAppBuildCredentials(
            iosDistributionType: $iosDistributionType,
            distributionCertificateId: $distributionCertId,
            provisioningProfileId: $provisioningProfileId,
            iosAppCredentialsId: $iosAppCredentialsId
        );

        if (!$createIosAppBuildCredentialsResponse) {
            throw new ServerErrorException('Failed to create build creditials');
        }

        return [
            'apple_app_identifier_id' => $appleAppIdentifierId,
            'ios_app_credentials_id' => $iosAppCredentialsId,
            'asc_key_id' => $ascKeyId,
            'distribution_cert_id' => $distributionCertId,
            'provisioning_profile_id' => $provisioningProfileId,
            'push_key_id' => $pushKeyId
        ];
    }

    /**
     * Deletes the iOS credentials for a project.
     * @param string $iosAppCredentialsId - the id of the iOS app credentials to delete.
     * @return array|null - the response from the server.
     */
    public function deleteProjectCredentials(string $iosAppCredentialsId): ?array
    {
        $response = $this->expoClient->request('POST', $this->deleteIosAppCredentialsQuery->build(
            iosAppCredentialsId: $iosAppCredentialsId
        ));

        return $response ?? null;
    }

    /**
     * Batch create the credentials needed for an iOS app.
     * @param string $distributionCertP12 - the distribution certificate in p12 format.
     * @param string $distributionCertPassword - the password for the distribution certificate.
     * @param string $appleProvisioningProfile - the provisioning profile for the app.
     * @param string $pushKeyP8 - the push key in p8 format.
     * @param string $pushKeyIdentifier - the identifier for the push key.
     * @param string $ascKeyP8 - the ASC API key in p8 format.
     * @param string $ascKeyIdentifier - the identifier for the ASC API key.
     * @param string $ascKeyIssuerIdentifier - the issuer identifier for the ASC API key.
     * @param string $ascName - the name for the ASC API key.
     * @param string $accountId - the id of the account to create the credentials for.
     * @param string $appleAppIdentifierId - the id of the apple app identifier to create the credentials for.
     * @return array - the ids of the created credentials.
     */
    private function batchPreAppCredentialCreationQueries(
        string $distributionCertP12,
        string $distributionCertPassword,
        string $appleProvisioningProfile,
        string $pushKeyP8,
        string $pushKeyIdentifier,
        string $ascKeyP8,
        string $ascKeyIdentifier,
        string $ascKeyIssuerIdentifier,
        string $ascName,
        string $accountId,
        string $appleAppIdentifierId
    ): array {
        $preparedCreateAppleDistributionCertificateResponse = $this->createAppleDistributionCertificateQuery->build(
            certP12: $distributionCertP12,
            certPassword: $distributionCertPassword,
            accountId: $accountId
        );

        $preparedCreateAppleProvisioningProfileResponse = $this->createAppleProvisioningProfileQuery->build(
            appleProvisioningProfile: $appleProvisioningProfile,
            appleAppIdentifierId: $appleAppIdentifierId,
            accountId: $accountId
        );

        $preparedCreateApplePushKeyResponse = $this->createApplePushKeyQuery->build(
            keyIdentifier: $pushKeyIdentifier,
            keyP8: $pushKeyP8,
            accountId: $accountId,
            appleTeamId: $this->config->appleTeamId
        );

        $preparedCreateAscApiKeyResponse = $this->createAscApiKeyQuery->build(
            keyP8: $ascKeyP8,
            keyIdentifier: $ascKeyIdentifier,
            issuerIdentifier: $ascKeyIssuerIdentifier,
            name: $ascName,
            accountId: $accountId
        );
      
        $batchResponse = $this->expoClient->request('POST', [
            $preparedCreateAppleDistributionCertificateResponse,
            $preparedCreateAppleProvisioningProfileResponse,
            $preparedCreateApplePushKeyResponse,
            $preparedCreateAscApiKeyResponse
        ]);

        return [
            'createAppleDistributionCertificate' => $batchResponse[0]['data']['appleDistributionCertificate']['createAppleDistributionCertificate'],
            'createAppleProvisioningProfile' => $batchResponse[1]['data']['appleProvisioningProfile']['createAppleProvisioningProfile'],
            'createApplePushKey' => $batchResponse[2]['data']['applePushKey']['createApplePushKey'],
            'createAppStoreConnectApiKey' => $batchResponse[3]['data']['appStoreConnectApiKey']['createAppStoreConnectApiKey']
        ];
    }

    /**
     * Gets all apple app identifiers for the account.
     * @return array - the requested apple app identifiers.
     */
    private function getAllAppleAppIdentifiers(): array
    {
        $response = $this->expoClient->request('POST', $this->getAllAppleAppIdentifiersQuery->build(
            accountName: $this->config->accountName
        ));
        return $response['data']['account']['byName']['appleAppIdentifiers'] ?? null;
    }

    /**
     * Gets the apple app identifier for the given bundle identifier or creates a new one if it doesn't exist.
     * @param string $bundleIdentifier - the bundle identifier to get the apple app identifier for.
     * @param string $accountId - the id of the account to create the apple app identifier for if required.
     * @throws ServerErrorException - on failure to create or get a new apple app identifier.
     * @return string - the id of the apple app identifier.
     */
    private function getOrCreateAppleAppIdentifier(
        string $bundleIdentifier,
        string $accountId
    ): string {
        $appleAppIdentifierId = null;

        $getAllAppleAppIdentifiersResponse = $this->getAllAppleAppIdentifiers();
        $existingAppleIdentifier = reset(array_filter($getAllAppleAppIdentifiersResponse, function ($appleAppIdentifier) use ($bundleIdentifier) {
            return $appleAppIdentifier['bundleIdentifier'] === $bundleIdentifier;
        }));

        if ($existingAppleIdentifier) {
            $appleAppIdentifierId = $existingAppleIdentifier['id'];
        } else {
            // create apple a new app identifier.
            $createAppleAppIdentifierResponse = $this->createAppleAppIdentifier(
                bundleIdentifier: $bundleIdentifier,
                accountId: $accountId
            );
            $appleAppIdentifierId = $createAppleAppIdentifierResponse['id'];
        }

        if (!$appleAppIdentifierId) {
            throw new ServerErrorException('Failed to get existing or create a new apple app identifier');
        }

        return $appleAppIdentifierId;
    }

    /**
     * Creates a new apple app identifier.
     * @param string $bundleIdentifier - the bundle identifier to create the apple app identifier for.
     * @param string $accountId - the id of the account to create the apple app identifier for.
     * @return array|null - the response from the server.
     */
    private function createAppleAppIdentifier(
        string $bundleIdentifier,
        string $accountId,
    ): ?array {
        $response = $this->expoClient->request('POST', $this->createAppleAppIdentifierQuery->build(
            bundleIdentifier: $bundleIdentifier,
            accountId: $accountId
        ));
        return $response['data']['appleAppIdentifier']['createAppleAppIdentifier'] ?? null;
    }

    /**
     * Creates a new ios app credentials.
     * @param string $appleAppIdentifierId - the id of the apple app identifier to create the ios app credentials for.
     * @param string $appId - the id of the app to create the ios app credentials for.
     * @param string|null $pushKeyId - the id of the push key to create the ios app credentials for.
     * @param string|null $ascKeyId - the id of the ASC API key to create the ios app credentials for.
     * @return array|null - the response from the server.
     */
    private function createIosAppCredentials(
        string $appleAppIdentifierId,
        string $appId,
        ?string $pushKeyId,
        ?string $ascKeyId
    ): ?array {
        $response = $this->expoClient->request('POST', $this->createIosAppCredentialsQuery->build(
            appleAppIdentifierId: $appleAppIdentifierId,
            appId: $appId,
            pushKeyId: $pushKeyId,
            ascKeyId: $ascKeyId
        ));
        return $response['data']['iosAppCredentials']['createIosAppCredentials']?? null;
    }

    /**
     * Creates a new ios app build credentials.
     * @param string $distributionCertificateId - the id of the distribution certificate.
     * @param string $provisioningProfileId - the id of the provisioning profile.
     * @param string $iosAppCredentialsId - the id of the ios app credentials.
     * @param string $iosDistributionType - the type of distribution to use for the app. e.g. DEVELOPMENT.
     * @return array|null - the response from the server.
     */
    private function createIosAppBuildCredentials(
        string $distributionCertificateId,
        string $provisioningProfileId,
        string $iosAppCredentialsId,
        string $iosDistributionType = 'DEVELOPMENT'
    ): ?array {
        $response = $this->expoClient->request('POST', $this->createIosAppBuildCredentialsQuery->build(
            distributionCertificateId: $distributionCertificateId,
            provisioningProfileId: $provisioningProfileId,
            iosAppCredentialsId: $iosAppCredentialsId,
            iosDistributionType: $iosDistributionType
        ));
        return $response['data']['iosAppBuildCredentials']['createIosAppBuildCredentials'] ?? null;
    }

    /**
     * Creates a new apple distribution certificate.
     * @param string $distributionCertP12 - the distribution certificate in p12 format.
     * @param string $distributionCertPassword - the password for the distribution certificate.
     * @param string $accountId - the id of the account to create the distribution certificate for.
     * @return array|null - the response from the server.
     */
    private function createAppleDistributionCertificate(
        string $distributionCertP12,
        string $distributionCertPassword,
        string $accountId,
    ): ?array {
        $response = $this->expoClient->request('POST', $this->createAppleDistributionCertificateQuery->build(
            certP12: $distributionCertP12,
            certPassword: $distributionCertPassword,
            accountId: $accountId
        ));
        return $response['data']['appleDistributionCertificate']['createAppleDistributionCertificate'] ?? null;
    }

    /**
     * Creates a new apple provisioning profile.
     * @param string $appleProvisioningProfile - the provisioning profile for the app.
     * @param string $appleAppIdentifierId - the id of the apple app identifier to create the provisioning profile for.
     * @param string $accountId - the id of the account to create the provisioning profile for.
     * @return array|null - the response from the server.
     */
    private function createAppleProvisioningProfile(
        string $appleProvisioningProfile,
        string $appleAppIdentifierId,
        string $accountId
    ): ?array {
        $response = $this->expoClient->request('POST', $this->createAppleProvisioningProfileQuery->build(
            appleProvisioningProfile: $appleProvisioningProfile,
            appleAppIdentifierId: $appleAppIdentifierId,
            accountId: $accountId
        ));
        return $response['data']['appleProvisioningProfile']['createAppleProvisioningProfile'] ?? null;
    }

    /**
     * Creates a new apple push key.
     * @param string $pushKeyIdentifier - the identifier for the push key.
     * @param string $pushKeyP8 - the push key in p8 format.
     * @param string $accountId - the id of the account to create the push key for.
     * @return array|null - the response from the server.
     */
    private function createApplePushKey(
        string $pushKeyIdentifier,
        string $pushKeyP8,
        string $accountId
    ): ?array {
        $response = $this->expoClient->request('POST', $this->createApplePushKeyQuery->build(
            keyIdentifier: $pushKeyIdentifier,
            keyP8: $pushKeyP8,
            accountId: $accountId,
            appleTeamId: $this->config->appleTeamId
        ));
        return $response['data']['applePushKey']['createApplePushKey'] ?? null;
    }

    /**
     * Creates a new ASC API key.
     * @param string $ascKeyP8 - the ASC API key in p8 format.
     * @param string $ascKeyIdentifier - the identifier for the ASC API key.
     * @param string $ascKeyIssuerIdentifier - the issuer identifier for the ASC API key.
     * @param string $ascName - the name for the ASC API key.
     * @param string $accountId - the id of the account to create the ASC API key for.
     * @return array|null - the response from the server.
     */
    private function createAscApiKey(
        string $ascKeyP8,
        string $ascKeyIdentifier,
        string $ascKeyIssuerIdentifier,
        string $ascName,
        string $accountId
    ): ?array {
        $response = $this->expoClient->request('POST', $this->createAscApiKeyQuery->build(
            keyP8: $ascKeyP8,
            keyIdentifier: $ascKeyIdentifier,
            issuerIdentifier: $ascKeyIssuerIdentifier,
            name: $ascName,
            accountId: $accountId
        ));
        return $response['data']['appStoreConnectApiKey']['createAppStoreConnectApiKey'] ?? null;
    }

    /**
     * Sets the ASC API key for the given ios app credentials id.
     * @param string $iosAppCredentialsId - the id of the ios app credentials to set the ASC API key for.
     * @param string $ascKeyId - the id of the ASC API key to set.
     * @return array|null - the response from the server.
     */
    private function setAscApiKeyForIosAppCredentials(
        string $iosAppCredentialsId,
        string $ascKeyId
    ): ?array {
        $response = $this->expoClient->request('POST', $this->setAscApiKeyForIosAppCredentialsQuery->build(
            iosAppCredentialsId: $iosAppCredentialsId,
            ascApiKeyId: $ascKeyId
        ));
        return $response['data']['iosAppCredentials']['setAppStoreConnectApiKeyForSubmissions'] ?? null;
    }

    /**
     * Sets the push key for the given ios app credentials id.
     * @param string $iosAppCredentialsId - the id of the ios app credentials to set the push key for.
     * @param string $pushKeyId - the id of the push key to set.
     * @return array|null - the response from the server.
     */
    private function setPushKeyForIosAppCredentials(
        string $iosAppCredentialsId,
        string $pushKeyId
    ): ?array {
        $response = $this->expoClient->request('POST', $this->setPushKeyForIosAppCredentialsQuery->build(
            iosAppCredentialsId: $iosAppCredentialsId,
            pushKeyId: $pushKeyId
        ));
        return $response['data']['iosAppCredentials']['setPushKey'] ?? null;
    }
}
