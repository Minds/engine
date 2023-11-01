<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Queries\iOS\Credentials;

/**
 * Query to set a push key for existing iOS app credentials, manually.
 */
class SetPushKeyForIosAppCredentialsQuery
{
    /**
     * Build the query.
     * @param string $iosAppCredentialsId - The ID of the iOS app credentials to update.
     * @param string $pushKeyId - The ID of the push key to add.
     * @return array - The query.
     */
    public function build(
        string $iosAppCredentialsId,
        string $pushKeyId
    ): array {
        return [
            "operationName" => "SetPushKeyForIosAppCredentials",
            "variables" => [
               "iosAppCredentialsId" => $iosAppCredentialsId,
               "pushKeyId" => $pushKeyId
            ],
            "query" => '
                mutation SetPushKeyForIosAppCredentials($iosAppCredentialsId: ID!, $pushKeyId: ID!) {
                    iosAppCredentials {
                        setPushKey(id: $iosAppCredentialsId, pushKeyId: $pushKeyId) {
                            id
                            pushKey {
                                id
                                keyIdentifier
                                __typename
                            }
                            __typename
                        }
                        __typename
                    }
                }'
         ];
    }
}
