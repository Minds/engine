<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Queries\Credentials\iOS;

/**
 * Query to add an ASC key to Expo.
 */
class CreateAscApiKeyQuery
{
    /**
     * Build the query.
     * @param string $keyIdentifier - The key identifier.
     * @param string $keyP8 - The key in P8 format.
     * @param string $issuerIdentifier - The issuer identifier.
     * @param string $name - The name of the key.
     * @param string $accountId - The ID of the account to use.
     * @return array - The query.
     */
    public function build(
        string $keyIdentifier,
        string $keyP8,
        string $issuerIdentifier,
        string $name,
        string $accountId
    ): array {
        return [
            "operationName" => "CreateAscApiKey",
            "variables" => [
               "ascApiKeyInput" => [
                  "keyIdentifier" => $keyIdentifier,
                  "keyP8" => $keyP8,
                  "issuerIdentifier" => $issuerIdentifier,
                  "name" => $name
               ],
               "accountId" => $accountId
            ],
            "query" => '
                mutation CreateAscApiKey($ascApiKeyInput: AppStoreConnectApiKeyInput!, $accountId: ID!) {
                    appStoreConnectApiKey {
                        createAppStoreConnectApiKey(
                            accountId: $accountId
                            appStoreConnectApiKeyInput: $ascApiKeyInput
                        ) {
                            ...AppStoreConnectApiKeyData
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
         ] ;
    }
}
