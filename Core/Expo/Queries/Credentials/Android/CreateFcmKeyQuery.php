<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Queries\Credentials\Android;

/**
 * Query to add an FCM key / GCM token to Expo.
 */
class CreateFcmKeyQuery
{
    /**
     * Build the query.
     * @param string $accountId - The ID of the account to add the FCM key to.
     * @param string $googleCloudMessagingToken - The FCM key / GCM token to add.
     * @return array - The query.
     */
    public function build(
        string $accountId,
        string $googleCloudMessagingToken
    ): array {
        return [
            
            "operationName" => "CreateFcmKey",
            "variables" => [
               "androidFcmInput" => [
                  "version" => "LEGACY",
                  "credential" => $googleCloudMessagingToken
               ],
               "accountId" => $accountId
            ],
            "query" => '
                mutation CreateFcmKey($androidFcmInput: AndroidFcmInput!, $accountId: ID!) {
                    androidFcm {
                        createAndroidFcm(androidFcmInput: $androidFcmInput, accountId: $accountId) {
                            ...AndroidFcmData
                            __typename
                        }
                        __typename
                    }
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
                }'
        ];
    }
}
