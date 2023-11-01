<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Queries\Android\Credentials;

/**
 * Query to create android app credentials IN Expo.
 */
class CreateAndroidAppCredentialsQuery
{
    /**
     * Build the query.
     * @param string $projectId - The ID of the project to create the credentials for.
     * @param string $applicationIdentifier - The application identifier to create the credentials for.
     * @param string|null $fcmKeyId - The ID of the FCM key to add.
     * @param string|null $googleServiceAccountKeyId - The ID of the google service account key to add.
     * @return array - The query.
     */
    public function build(
        string $projectId,
        string $applicationIdentifier,
        ?string $fcmKeyId = null,
        ?string $googleServiceAccountKeyId  = null
    ): array {
        $androidAppCredentialsInput = [];

        if ($fcmKeyId) {
            $androidAppCredentialsInput['fcmId'] = $fcmKeyId;
        }

        if ($googleServiceAccountKeyId) {
            $androidAppCredentialsInput['googleServiceAccountKeyForSubmissionsId'] = $googleServiceAccountKeyId;
        }

        return [
            "operationName" => "CreateAndroidAppCredentials",
            "variables" => [
                "androidAppCredentialsInput" => $androidAppCredentialsInput,
                "appId" => $projectId,
                "applicationIdentifier" => $applicationIdentifier
            ],
            "query" => '
                mutation CreateAndroidAppCredentials(
                    $androidAppCredentialsInput: AndroidAppCredentialsInput!
                    $appId: ID!
                    $applicationIdentifier: String!
                ) {
                    androidAppCredentials {
                        createAndroidAppCredentials(
                            androidAppCredentialsInput: $androidAppCredentialsInput
                            appId: $appId
                            applicationIdentifier: $applicationIdentifier
                        ) {
                            ...AndroidAppCredentialsData
                            __typename
                        }
                        __typename
                    }
                }
                
                fragment AndroidAppCredentialsData on AndroidAppCredentials {
                    id
                    app {
                        id
                        fullName
                        name
                        username
                        slug
                        __typename
                    }
                    applicationIdentifier
                    androidFcm {
                        ...AndroidFcmData
                        __typename
                    }
                    androidAppBuildCredentialsList {
                        ...AndroidAppBuildCredentialsData
                        __typename
                    }
                    googleServiceAccountKeyForSubmissions {
                        ...GoogleServiceAccountKeyData
                        __typename
                    }
                    isLegacy
                    __typename
                }
                
                fragment AndroidFcmData on AndroidFcm {
                    id
                    version
                    snippet {
                        __typename
                        ... on FcmSnippetLegacy {
                            firstFourCharacters
                            lastFourCharacters
                            __typename
                        }
                        ... on FcmSnippetV1 {
                            projectId
                            keyId
                            serviceAccountEmail
                            clientId
                            __typename
                        }
                    }
                    credential
                    version
                    createdAt
                    updatedAt
                    __typename
                }
                
                fragment AndroidAppBuildCredentialsData on AndroidAppBuildCredentials {
                    id
                    name
                    isDefault
                    isLegacy
                    androidKeystore {
                        ...AndroidKeystoreData
                        __typename
                    }
                    __typename
                }
                
                fragment AndroidKeystoreData on AndroidKeystore {
                    id
                    type
                    keyAlias
                    md5CertificateFingerprint
                    sha1CertificateFingerprint
                    sha256CertificateFingerprint
                    createdAt
                    updatedAt
                    keystore
                    keystorePassword
                    keyAlias
                    keyPassword
                    __typename
                }
                
                fragment GoogleServiceAccountKeyData on GoogleServiceAccountKey {
                    id
                    account {
                        id
                        __typename
                    }
                    projectIdentifier
                    privateKeyIdentifier
                    clientEmail
                    clientIdentifier
                    createdAt
                    updatedAt
                    __typename
                }'
            ];
    }
}
