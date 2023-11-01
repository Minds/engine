<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Queries\iOS\Credentials;

/**
 * Query to set the ASC key for existing iOS app credentials, manually.
 */
class SetAscApiKeyForIosAppCredentialsQuery
{
    /**
     * Build the query.
     * @param string $iosAppCredentialsId - The ID of the iOS app credentials to update.
     * @param string $ascApiKeyId - The ID of the ASC key to add.
     * @return array - The query.
     */
    public function build(
        string $iosAppCredentialsId,
        string $ascApiKeyId
    ): array {
        return [
            "operationName" => "SetAscApiKeyForIosAppCredentials",
            "variables" => [
               "iosAppCredentialsId" => $iosAppCredentialsId,
               "ascApiKeyId" => $ascApiKeyId
            ],
            "query" => '
                mutation SetAscApiKeyForIosAppCredentials($iosAppCredentialsId: ID!, $ascApiKeyId: ID!) {
                    iosAppCredentials {
                        setAppStoreConnectApiKeyForSubmissions(
                            id: $iosAppCredentialsId
                            ascApiKeyId: $ascApiKeyId
                        ) {
                            id
                            appStoreConnectApiKeyForSubmissions {
                                ...AppStoreConnectApiKeyData
                                __typename
                            }
                            __typename
                        }
                        __typename
                    }
                }
                
                fragment AppStoreConnectApiKeyData on AppStoreConnectApiKey {
                    id
                    appleTeam {
                        ...AppleTeamData
                        __typename
                    }
                    issuerIdentifier
                    keyIdentifier
                    name
                    roles
                    createdAt
                    updatedAt
                    __typename
                }
                
                fragment AppleTeamData on AppleTeam {
                    id
                    appleTeamIdentifier
                    appleTeamName
                    __typename
                }'
        ];
    }
}
