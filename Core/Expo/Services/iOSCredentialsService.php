<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Services;

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
use Minds\Core\MultiTenant\Configs\Manager as MultiTenantConfigsManager;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;

/**
 * Service for managing iOS credentials in Expo.
 */
class iOSCredentialsService extends BatchExpoGqlQueryHandler
{
    public function __construct(
        private ExpoGqlClient $expoGqlClient,
        private ExpoConfig $expoConfig,
        private Config $config,
        private MultiTenantDataService $multiTenantDataService,
        private MultiTenantConfigsManager $multiTenantConfigsManager,
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
     * @param string|null $pushKeyP8 - the push key in p8 format.
     * @param string|null $pushKeyIdentifier - the identifier for the push key.
     * @param string|null $ascKeyP8 - the ASC API key in p8 format.
     * @param string|null $ascKeyIdentifier - the identifier for the ASC API key.
     * @param string|null $ascKeyIssuerIdentifier - the issuer identifier for the ASC API key.
     * @param string|null $ascName - the name for the ASC API key.
     * @throws ServerErrorException - on failure to create credentials.
     * @return array - the ids of the created credentials.
     */
    public function setupProjectCredentials(
        string $bundleIdentifier,
        string $iosDistributionType,
        string $distributionCertP12,
        string $distributionCertPassword,
        string $appleProvisioningProfile,
        ?string $pushKeyP8 = null,
        ?string $pushKeyIdentifier = null,
        ?string $ascKeyP8 = null,
        ?string $ascKeyIdentifier = null,
        ?string $ascKeyIssuerIdentifier = null,
        ?string $ascName = null
    ): array {
        $tenantId = $this->config->get('tenant_id') ?? throw new ServerErrorException('No tenant id set');
        $tenantConfigs = $this->multiTenantDataService->getTenantFromId($tenantId);
        $projectId = $tenantConfigs?->config?->expoProjectId ??
            throw new ServerErrorException('No expo_project_id configured for tenant');

        $appleAppIdentifierId = $this->getOrCreateAppleAppIdentifier(bundleIdentifier: $bundleIdentifier);

        // create resources for the different credentials that we want to set up.
        $batchPreAppCredentialCreationQueriesResponse = $this->batchPreAppCredentialCreationQueries(
            appleAppIdentifierId: $appleAppIdentifierId,
            distributionCertP12: $distributionCertP12,
            distributionCertPassword: $distributionCertPassword,
            appleProvisioningProfile: $appleProvisioningProfile,
            pushKeyP8: $pushKeyP8,
            pushKeyIdentifier: $pushKeyIdentifier,
            ascKeyP8: $ascKeyP8,
            ascKeyIdentifier: $ascKeyIdentifier,
            ascKeyIssuerIdentifier: $ascKeyIssuerIdentifier,
            ascName: $ascName,
        );

        $distributionCertId = $batchPreAppCredentialCreationQueriesResponse['createAppleDistributionCertificate']['id'] ??
            throw new ServerErrorException('Failed to create distribution certificate in Expo');
        $provisioningProfileId = $batchPreAppCredentialCreationQueriesResponse['createAppleProvisioningProfile']['id'] ??
            throw new ServerErrorException('Failed to create provisioning profile in Expo');
        
        // for optional values provided, check that they were successfully created in the batch query.
        if ($pushKeyP8 && $pushKeyIdentifier) {
            $pushKeyId = $batchPreAppCredentialCreationQueriesResponse['createApplePushKey']['id'] ??
                throw new ServerErrorException('Failed to create apple push key');
        }

        if ($ascKeyP8 && $ascKeyIdentifier && $ascKeyIssuerIdentifier && $ascName) {
            $ascKeyId = $batchPreAppCredentialCreationQueriesResponse['createAppStoreConnectApiKey']['id'] ??
                throw new ServerErrorException('Failed to create ASC API key in Expo');
        }

        // Create iOS app credentials (with the created resources if provided).
        $createIosAppCredentialsResponse = $this->createIosAppCredentials(
            appleAppIdentifierId: $appleAppIdentifierId,
            appId: $projectId,
            pushKeyId: $pushKeyId ?? null,
            ascKeyId: $ascKeyId ?? null
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

        if (!$createIosAppBuildCredentialsResponse || !$iosAppBuildCredentialsId = $createIosAppBuildCredentialsResponse['id']) {
            throw new ServerErrorException('Failed to create build creditials');
        }

        $this->multiTenantConfigsManager->upsertConfigs(
            iosAppCredentialsId: $iosAppCredentialsId,
            iosAppBuildCredentialsId: $iosAppBuildCredentialsId
        );

        return [
            'apple_app_identifier_id' => $appleAppIdentifierId,
            'ios_app_credentials_id' => $iosAppCredentialsId,
            'ios_app_build_credentials_id' => $iosAppBuildCredentialsId,
            'asc_key_id' => $ascKeyId ?? null,
            'distribution_cert_id' => $distributionCertId,
            'provisioning_profile_id' => $provisioningProfileId,
            'push_key_id' => $pushKeyId ?? null
        ];
    }

    /**
     * Update project credentials.
     * @param string|null $pushKeyP8 - the push key in p8 format.
     * @param string|null $pushKeyIdentifier - the identifier for the push key.
     * @param string|null $ascKeyP8 - the ASC API key in p8 format.
     * @param string|null $ascKeyIdentifier - the identifier for the ASC API key.
     * @param string|null $ascKeyIssuerIdentifier - the issuer identifier for the ASC API key.
     * @param string|null $ascName - the name for the ASC API key.
     * @return array - the ids of the created credentials.
     */
    public function updateProjectCredentials(
        ?string $pushKeyP8,
        ?string $pushKeyIdentifier,
        ?string $ascKeyP8,
        ?string $ascKeyIdentifier,
        ?string $ascKeyIssuerIdentifier,
        ?string $ascName,
    ): array {
        $hasFullPushKey = $pushKeyP8 && $pushKeyIdentifier;
        $hasFullAscKey = $ascKeyP8 && $ascKeyIdentifier && $ascKeyIssuerIdentifier && $ascName;

        if (!$hasFullPushKey && !$hasFullAscKey) {
            throw new UserErrorException('Must fully provide either push key params or asc key params');
        }

        $tenantId = $this->config->get('tenant_id') ?? throw new ServerErrorException('No tenant id set');
        $tenantConfigs = $this->multiTenantDataService->getTenantFromId($tenantId);
        $iosAppCredentialsId = $tenantConfigs?->config?->expoIosAppCredentialsId ??
            throw new ServerErrorException('No iOS app credentials id configured for tenant');

        $batchUpdateQueriesResponse = $this->batchUpdateCreationQueries(
            pushKeyP8: $pushKeyP8,
            pushKeyIdentifier: $pushKeyIdentifier,
            ascKeyP8: $ascKeyP8,
            ascKeyIdentifier: $ascKeyIdentifier,
            ascKeyIssuerIdentifier: $ascKeyIssuerIdentifier,
            ascName: $ascName
        );

        if (!$batchUpdateQueriesResponse) {
            throw new ServerErrorException('Failed to create pre-requisite resources to update android app credentials');
        }

        $pushKeyId = $batchUpdateQueriesResponse['createApplePushKey']['id'] ?? null;
        $ascKeyId = $batchUpdateQueriesResponse['createAppStoreConnectApiKey']['id'] ?? null;

        $batchUpdateApplyQueriesResponse = $this->batchAppCredentialUpdateApplyQueries(
            iosAppCredentialsId: $iosAppCredentialsId,
            pushKeyId: $pushKeyId,
            ascKeyId: $ascKeyId,
        );

        if (!$batchUpdateApplyQueriesResponse) {
            throw new ServerErrorException('Failed to apply updates to iOS app credentials');
        }

        return [
            'ios_app_credentials_id' => $iosAppCredentialsId,
            'asc_key_id' => $ascKeyId,
            'push_key_id' => $pushKeyId
        ];
    }

    /**
     * Deletes the iOS credentials for the current tenants project.
     * @return bool - true if there is a response.
     */
    public function deleteProjectCredentials(): bool
    {
        $tenantId = $this->config->get('tenant_id') ?? throw new ServerErrorException('No tenant id set');
        $tenant = $this->multiTenantDataService->getTenantFromId($tenantId);

        $iosAppCredentialsId = $tenant?->config?->expoIosAppCredentialsId ??
            throw new ServerErrorException('No iOS app credentials id configured.');

        $response = $this->expoGqlClient->request($this->deleteIosAppCredentialsQuery->build(
            iosAppCredentialsId: $iosAppCredentialsId
        ));

        return (bool) $response;
    }

    /**
     * Batch create the credentials needed for an iOS app.
     * @param string $appleAppIdentifierId - the id of the apple app identifier to create the credentials for.
     * @param string $distributionCertP12 - the distribution certificate in p12 format.
     * @param string $distributionCertPassword - the password for the distribution certificate.
     * @param string $appleProvisioningProfile - the provisioning profile for the app.
     * @param string|null $pushKeyP8 - the push key in p8 format.
     * @param string|null $pushKeyIdentifier - the identifier for the push key.
     * @param string|null $ascKeyP8 - the ASC API key in p8 format.
     * @param string|null $ascKeyIdentifier - the identifier for the ASC API key.
     * @param string|null $ascKeyIssuerIdentifier - the issuer identifier for the ASC API key.
     * @param string|null $ascName - the name for the ASC API key.
     * @return array - the ids of the created credentials.
     */
    private function batchPreAppCredentialCreationQueries(
        string $appleAppIdentifierId,
        string $distributionCertP12,
        string $distributionCertPassword,
        string $appleProvisioningProfile,
        ?string $pushKeyP8 = null,
        ?string $pushKeyIdentifier = null,
        ?string $ascKeyP8 = null,
        ?string $ascKeyIdentifier = null,
        ?string $ascKeyIssuerIdentifier = null,
        ?string $ascName = null
    ): array {
        $queries = [];

        $queries[] = $this->createAppleDistributionCertificateQuery->build(
            certP12: $distributionCertP12,
            certPassword: $distributionCertPassword,
            accountId: $this->expoConfig->accountId
        );

        $queries[] = $this->createAppleProvisioningProfileQuery->build(
            appleProvisioningProfile: $appleProvisioningProfile,
            appleAppIdentifierId: $appleAppIdentifierId,
            accountId: $this->expoConfig->accountId
        );

        if ($pushKeyP8 && $pushKeyIdentifier) {
            $queries[] = $this->createApplePushKeyQuery->build(
                keyIdentifier: $pushKeyIdentifier,
                keyP8: $pushKeyP8,
                accountId: $this->expoConfig->accountId,
                appleTeamId: $this->expoConfig->appleTeamId
            );
        }

        if ($ascKeyP8 && $ascKeyIdentifier && $ascKeyIssuerIdentifier && $ascName) {
            $queries[] = $this->createAscApiKeyQuery->build(
                keyP8: $ascKeyP8,
                keyIdentifier: $ascKeyIdentifier,
                issuerIdentifier: $ascKeyIssuerIdentifier,
                name: $ascName,
                accountId: $this->expoConfig->accountId
            );
        }
      
        $batchResponse = $this->expoGqlClient->request($queries);
        return $this->formatBatchResponse($batchResponse);
    }

    /**
     * Batch the creation queries for an update to existing credentials.
     * @param string|null $pushKeyIdentifier - the identifier for the push key.
     * @param string|null $pushKeyP8 - the push key in p8 format.
     * @param string|null $ascKeyP8 - the ASC API key in p8 format.
     * @param string|null $ascKeyIdentifier - the identifier for the ASC API key.
     * @param string|null $ascKeyIssuerIdentifier - the issuer identifier for the ASC API key.
     * @param string|null $ascName - the name for the ASC API key.
     * @return array - the ids of the created credentials.
     */
    private function batchUpdateCreationQueries(
        ?string $pushKeyIdentifier = null,
        ?string $pushKeyP8 = null,
        ?string $ascKeyP8 = null,
        ?string $ascKeyIdentifier = null,
        ?string $ascKeyIssuerIdentifier = null,
        ?string $ascName = null,
    ): array {
        $queries = [];

        if ($pushKeyIdentifier && $pushKeyP8) {
            $queries[] = $this->createApplePushKeyQuery->build(
                keyIdentifier: $pushKeyIdentifier,
                keyP8: $pushKeyP8,
                accountId: $this->expoConfig->accountId,
                appleTeamId: $this->expoConfig->appleTeamId
            );
        }

        if ($ascKeyP8 && $ascKeyIdentifier && $ascKeyIssuerIdentifier && $ascName) {
            $queries[] = $this->createAscApiKeyQuery->build(
                keyP8: $ascKeyP8,
                keyIdentifier: $ascKeyIdentifier,
                issuerIdentifier: $ascKeyIssuerIdentifier,
                name: $ascName,
                accountId: $this->expoConfig->accountId
            );
        }

        $batchResponse = $this->expoGqlClient->request($queries);
        return $this->formatBatchResponse($batchResponse);
    }

    /**
     * Batch queries that apply created credentials to this tenants credentials in Expo.
     * @param string $iosAppCredentialsId - the id of the iOS app credentials to apply the updates to.
     * @param string|null $pushKeyId - the id of the push key to apply.
     * @param string|null $ascKeyId - the id of the ASC API key to apply.
     * @return array - the responses from the queries.
     */
    private function batchAppCredentialUpdateApplyQueries(
        string $iosAppCredentialsId = null,
        ?string $pushKeyId,
        ?string $ascKeyId = null,
    ): ?array {
        if (!$ascKeyId && !$pushKeyId) {
            throw new ServerErrorException('No updates to apply');
        }

        $queries = [];

        if ($pushKeyId) {
            $queries[] = $this->setPushKeyForIosAppCredentialsQuery->build(
                iosAppCredentialsId: $iosAppCredentialsId,
                pushKeyId: $pushKeyId
            );
        }

        if ($ascKeyId) {
            $queries[] = $this->setAscApiKeyForIosAppCredentialsQuery->build(
                iosAppCredentialsId: $iosAppCredentialsId,
                ascApiKeyId: $ascKeyId
            );
        }

        $batchResponse = $this->expoGqlClient->request($queries);
        return $this->formatBatchResponse($batchResponse);
    }

    /**
     * Gets all apple app identifiers for the account.
     * @return array - the requested apple app identifiers.
     */
    private function getAllAppleAppIdentifiers(): array
    {
        $response = $this->expoGqlClient->request($this->getAllAppleAppIdentifiersQuery->build(
            accountName: $this->expoConfig->accountName
        ));
        return $response['data']['account']['byName']['appleAppIdentifiers'] ?? [];
    }

    /**
     * Gets the apple app identifier for the given bundle identifier or creates a new one if it doesn't exist.
     * @param string $bundleIdentifier - the bundle identifier to get the apple app identifier for.
     * @throws ServerErrorException - on failure to create or get a new apple app identifier.
     * @return string - the id of the apple app identifier.
     */
    private function getOrCreateAppleAppIdentifier(
        string $bundleIdentifier
    ): string {
        $appleAppIdentifierId = null;

        $getAllAppleAppIdentifiersResponse = $this->getAllAppleAppIdentifiers();

        $existingAppleIdentifier = array_filter($getAllAppleAppIdentifiersResponse, function ($appleAppIdentifier) use ($bundleIdentifier) {
            return $appleAppIdentifier['bundleIdentifier'] === $bundleIdentifier;
        });
        $existingAppleIdentifier = reset($existingAppleIdentifier);

        if ($existingAppleIdentifier) {
            $appleAppIdentifierId = $existingAppleIdentifier['id'];
        } else {
            // create apple a new app identifier.
            $createAppleAppIdentifierResponse = $this->createAppleAppIdentifier(
                bundleIdentifier: $bundleIdentifier,
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
     * @return array|null - the response from the server.
     */
    private function createAppleAppIdentifier(
        string $bundleIdentifier
    ): ?array {
        $response = $this->expoGqlClient->request($this->createAppleAppIdentifierQuery->build(
            bundleIdentifier: $bundleIdentifier,
            accountId: $this->expoConfig->accountId
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
        $response = $this->expoGqlClient->request($this->createIosAppCredentialsQuery->build(
            appleAppIdentifierId: $appleAppIdentifierId,
            appId: $appId,
            pushKeyId: $pushKeyId,
            ascKeyId: $ascKeyId
        ));
        return $response['data']['iosAppCredentials']['createIosAppCredentials'] ?? null;
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
        $response = $this->expoGqlClient->request($this->createIosAppBuildCredentialsQuery->build(
            distributionCertificateId: $distributionCertificateId,
            provisioningProfileId: $provisioningProfileId,
            iosAppCredentialsId: $iosAppCredentialsId,
            iosDistributionType: $iosDistributionType
        ));
        return $response['data']['iosAppBuildCredentials']['createIosAppBuildCredentials'] ?? null;
    }
}
