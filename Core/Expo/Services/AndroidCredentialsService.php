<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Services;

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
use Minds\Core\MultiTenant\Services\MultiTenantDataService;
use Minds\Exceptions\ServerErrorException;

/**
 * Service for managing Android credentials in Expo.
 */
class AndroidCredentialsService
{
    public function __construct(
        private ExpoGqlClient $expoGqlClient,
        private ExpoConfig $expoConfig,
        private Config $config,
        private MultiTenantDataService $multiTenantDataService,
        private CreateAndroidKeystoreQuery $createAndroidKeystoreQuery,
        private CreateAndroidAppCredentialsQuery $createAndroidAppCredentialsQuery,
        private CreateAndroidAppBuildCredentialsQuery $createAndroidAppBuildCredentialsQuery,
        private CreateGoogleServiceAccountKeyQuery $createGoogleServiceAccountKeyQuery,
        private SetGoogleServiceAccountKeyOnAndroidAppCredentialsQuery $setGoogleServiceAccountKeyOnAndroidAppCredentialsQuery,
        private CreateFcmKeyQuery $createFcmKeyQuery,
        private SetFcmKeyOnAndroidAppCredentialsQuery $setFcmKeyOnAndroidAppCredentialsQuery,
        private DeleteAndroidAppCredentialsQuery $deleteAndroidAppCredentialsQuery
    ) {
    }

    /**
     * Setup Android credentials for a project.
     * @param string $applicationIdentifier - the application identifier for the app.
     * @param string $androidKeystorePassword - the password for the keystore.
     * @param string $androidKeystoreKeyAlias - the alias for the keystore key.
     * @param string $androidKeystoreKeyPassword - the password for the keystore key.
     * @param string $androidBase64EncodedKeystore - the base64 encoded keystore.
     * @param string|null $googleServiceAccountJson - the json for the google service account.
     * @param string|null $googleCloudMessagingToken - the token for google cloud messaging for push support.
     * @throws ServerErrorException - if any of the steps fail.
     * @return array - the ids of the created credentials.
     */
    public function setupProjectCredentials(
        string $applicationIdentifier,
        string $androidKeystorePassword,
        string $androidKeystoreKeyAlias,
        string $androidKeystoreKeyPassword,
        string $androidBase64EncodedKeystore,
        ?string $googleServiceAccountJson = null,
        ?string $googleCloudMessagingToken  = null
    ): array {
        $tenantId = $this->config->get('tenant_id') ?? throw new ServerErrorException('No tenant id set');
        $tenantConfigs = $this->multiTenantDataService->getTenantFromId($tenantId);
        $projectId = $tenantConfigs?->config?->expoProjectId ??
            throw new ServerErrorException('No expo project id configured for tenant');

        $decodedGoogleServiceAccountJson = json_decode($googleServiceAccountJson ?? '', true) ?? null;

        $batchPreAppCredentialCreationQueriesResponse = $this->batchPreAppCredentialCreationQueries(
            accountId: $this->expoConfig->accountId,
            androidKeystorePassword: $androidKeystorePassword,
            androidKeystoreKeyAlias: $androidKeystoreKeyAlias,
            androidKeystoreKeyPassword: $androidKeystoreKeyPassword,
            androidBase64EncodedKeystore: $androidBase64EncodedKeystore,
            googleCloudMessagingToken: $googleCloudMessagingToken,
            googleServiceAccountCredentials: $decodedGoogleServiceAccountJson
        );

        if (!$batchPreAppCredentialCreationQueriesResponse) {
            throw new ServerErrorException('Failed to create pre-requisits for android app credentials');
        }

        $keystoreId = $batchPreAppCredentialCreationQueriesResponse['createAndroidKeystore']['id'] ??
            throw new ServerErrorException('Failed to create android keystore');

        if ($decodedGoogleServiceAccountJson) {
            $googleServiceAccountKeyId = $batchPreAppCredentialCreationQueriesResponse['createGoogleServiceAccountKey']['id'] ??
                throw new ServerErrorException('Failed to create google service account credentials');
        }

        if ($googleCloudMessagingToken) {
            $fcmKeyId = $batchPreAppCredentialCreationQueriesResponse["createAndroidFcm"]['id'] ??
                throw new ServerErrorException('Failed to create fcm key');
        }

        $createAndroidAppCredentialsResponse = $this->createAndroidAppCredentials(
            projectId: $projectId,
            applicationIdentifier: $applicationIdentifier,
            fcmKeyId: $fcmKeyId,
            googleServiceAccountKeyId: $googleServiceAccountKeyId
        );

        if (!$createAndroidAppCredentialsResponse || !$androidAppCredentialsId = $createAndroidAppCredentialsResponse['id']) {
            throw new ServerErrorException('Failed to create android app credentials');
        }

        $createAndroidAppBuildCredentialsResponse = $this->createAndroidAppBuildCredentials(
            androidAppCredentialsId: $androidAppCredentialsId,
            keystoreId: $keystoreId,
            name: $applicationIdentifier
        );

        if (!$createAndroidAppBuildCredentialsResponse) {
            throw new ServerErrorException('Failed to android app build credentials');
        }

        return [
            'android_app_credentials_id' => $androidAppCredentialsId,
            'keystore_id' => $keystoreId,
            'google_service_account_key_id' => $googleServiceAccountKeyId,
            'fcm_key_ud' => $fcmKeyId
        ];
    }

    /**
     * Delete the android app credentials.
     * @param string $androidAppCredentialsId - the id of the android app credentials to delete.
     * @return array|null - the response from the query or null on error.
     */
    public function deleteProjectCredentials(string $androidAppCredentialsId): ?array
    {
        $response = $this->expoGqlClient->request($this->deleteAndroidAppCredentialsQuery->build(
            androidAppCredentialsId: $androidAppCredentialsId
        ));
        return $response ?? null;
    }

    /**
     * Batch the pre-app credential creation queries.
     * @param string $accountId - the id of the account to create the credentials for.
     * @param string $androidKeystorePassword - the password for the keystore.
     * @param string $androidKeystoreKeyAlias - the alias for the keystore key.
     * @param string $androidKeystoreKeyPassword - the password for the keystore key.
     * @param string $androidBase64EncodedKeystore - the base64 encoded keystore.
     * @param string|null $googleCloudMessagingToken - the token for google cloud messaging for push support.
     * @param array|null $googleServiceAccountCredentials - the credentials for the google service account (an array from the decoded JSON file).
     * @return array - the responses from the queries.
     */
    private function batchPreAppCredentialCreationQueries(
        string $accountId,
        string $androidKeystorePassword,
        string $androidKeystoreKeyAlias,
        string $androidKeystoreKeyPassword,
        string $androidBase64EncodedKeystore,
        ?string $googleCloudMessagingToken,
        ?array $googleServiceAccountCredentials
    ): array {
        $queries = [];
        $queries[] = $this->createAndroidKeystoreQuery->build(
            accountId: $accountId,
            androidKeystorePassword: $androidKeystorePassword,
            androidKeystoreKeyAlias: $androidKeystoreKeyAlias,
            androidKeystoreKeyPassword: $androidKeystoreKeyPassword,
            androidBase64EncodedKeystore: $androidBase64EncodedKeystore
        );

        if ($googleServiceAccountCredentials) {
            $queries[] = $this->createGoogleServiceAccountKeyQuery->build(
                accountId: $accountId,
                googleServiceAccountCredentials: $googleServiceAccountCredentials
            );
        }

        if ($googleCloudMessagingToken) {
            $queries[] = $this->createFcmKeyQuery->build(
                accountId: $accountId,
                googleCloudMessagingToken: $googleCloudMessagingToken
            );
        }

        $batchResponse = $this->expoGqlClient->request($queries);

        $response = [];

        foreach ($batchResponse as $responseItem) {
            $arrayKey = array_key_first($responseItem['data']);
            $innerArrayKey = array_key_first($responseItem['data'][$arrayKey]);
            $response[$innerArrayKey] = $responseItem['data'][$arrayKey][$innerArrayKey];
        }

        return $response;
    }

    /**
     * Create android app credentials.
     * @param string $projectId - the id of the project to create the credentials for.
     * @param string $applicationIdentifier - the application identifier for the app.
     * @param string|null $fcmKeyId - the id of the fcm key to use.
     * @param string|null $googleServiceAccountKeyId - the id of the google service account key to use.
     * @return array|null - the response from the query or null on error.
     */
    private function createAndroidAppCredentials(
        string $projectId,
        string $applicationIdentifier,
        ?string $fcmKeyId = null,
        ?string $googleServiceAccountKeyId  = null
    ): ?array {
        $response =  $this->expoGqlClient->request($this->createAndroidAppCredentialsQuery->build(
            projectId: $projectId,
            applicationIdentifier: $applicationIdentifier,
            fcmKeyId: $fcmKeyId,
            googleServiceAccountKeyId: $googleServiceAccountKeyId
        ));
        return $response['data']['androidAppCredentials']['createAndroidAppCredentials'] ?? null;
    }

    /**
     * Create android app build credentials.
     * @param string $androidAppCredentialsId - the id of the android app credentials to create the build credentials for.
     * @param string $keystoreId - the id of the keystore to use.
     * @param string $name - the name of the build credentials.
     * @return array|null - the response from the query or null on error.
     */
    private function createAndroidAppBuildCredentials(
        string $androidAppCredentialsId,
        string $keystoreId,
        string $name
    ): ?array {
        $response =  $this->expoGqlClient->request($this->createAndroidAppBuildCredentialsQuery->build(
            androidAppCredentialsId: $androidAppCredentialsId,
            keystoreId: $keystoreId,
            name: $name
        ));
        return $response['data']['androidAppBuildCredentials']['createAndroidAppBuildCredentials'] ?? null;
    }

    /**
     * Upload an android keystore to Expo.
     * @param string $accountId - the id of the account to create the keystore for.
     * @param string $androidKeystorePassword - the password for the keystore.
     * @param string $androidKeystoreKeyAlias - the alias for the keystore key.
     * @param string $androidKeystoreKeyPassword - the password for the keystore key.
     * @param string $androidBase64EncodedKeystore - the base64 encoded keystore.
     * @return array|null - the response from the query or null on error.
     */
    private function createAndroidKeyStore(
        string $accountId,
        string $androidKeystorePassword,
        string $androidKeystoreKeyAlias,
        string $androidKeystoreKeyPassword,
        string $androidBase64EncodedKeystore
    ): ?array {
        $response = $this->expoGqlClient->request($this->createAndroidKeystoreQuery->build(
            accountId: $accountId,
            androidKeystorePassword: $androidKeystorePassword,
            androidKeystoreKeyAlias: $androidKeystoreKeyAlias,
            androidKeystoreKeyPassword: $androidKeystoreKeyPassword,
            androidBase64EncodedKeystore: $androidBase64EncodedKeystore
        ));
        return $response["data"]["androidKeystore"]["createAndroidKeystore"] ?? null;
    }

    /**
     * Upload a google service account to Expo.
     * @param string $accountId - the id of the account to create the key for.
     * @param array $googleServiceAccountCredentials - the credentials for the google service account (an array from the decoded JSON file).
     * @return array|null - the response from the query.
     */
    private function createGoogleServiceAccountKey(
        string $accountId,
        array $googleServiceAccountCredentials
    ): ?array {
        $response =  $this->expoGqlClient->request($this->createGoogleServiceAccountKeyQuery->build(
            accountId: $accountId,
            googleServiceAccountCredentials: $googleServiceAccountCredentials
        ));
        return $response['data']['googleServiceAccountKey']['createGoogleServiceAccountKey'] ?? null;
    }

    /**
     * Upload an "FCM key" using a googleCloudMessagingToken, to expo.
     * @param string $accountId - the id of the account to create the key for.
     * @param string $googleCloudMessagingToken - the token for google cloud messaging for push support.
     * @return array|null - the response from the query.
     */
    private function createFcmKey(
        string $accountId,
        string $googleCloudMessagingToken
    ): ?array {
        $response =  $this->expoGqlClient->request($this->createFcmKeyQuery->build(
            accountId: $accountId,
            googleCloudMessagingToken: $googleCloudMessagingToken
        ));
        return $response['data']['androidFcm']['createAndroidFcm'] ?? null;
    }

    /**
     * Set the google service account key on the android app credentials.
     * @param string $androidAppCredentialsId - the id of the android app credentials to set the key on.
     * @param string $googleServiceAccountKeyId - the id of the google service account key to set.
     * @return array|null - the response from the query.
     */
    private function setGoogleServiceAccountKeyOnAndroidAppCredentials(
        string $androidAppCredentialsId,
        string $googleServiceAccountKeyId
    ): ?array {
        $response = $this->expoGqlClient->request($this->setGoogleServiceAccountKeyOnAndroidAppCredentialsQuery->build(
            androidAppCredentialsId: $androidAppCredentialsId,
            googleServiceAccountKeyId: $googleServiceAccountKeyId
        ));
        return $response['data']['androidAppCredentials']['setGoogleServiceAccountKeyForSubmissions'] ?? null;
    }

    /**
     * Set the FCM key on the android app credentials by fcmKeyId.
     * @param string $androidAppCredentialsId - the id of the android app credentials to set the key on.
     * @param string $fcmKeyId - the id of the fcm key to set.
     * @return array|null - the response from the query.
     */
    private function setFcmKeyOnAndroidAppCredentials(
        string $androidAppCredentialsId,
        string $fcmKeyId
    ): ?array {
        $response =  $this->expoGqlClient->request($this->setFcmKeyOnAndroidAppCredentialsQuery->build(
            androidAppCredentialsId: $androidAppCredentialsId,
            fcmKeyId: $fcmKeyId
        ));
        return $response['data']['androidAppCredentials']['setFcm'] ?? null;
    }
}
