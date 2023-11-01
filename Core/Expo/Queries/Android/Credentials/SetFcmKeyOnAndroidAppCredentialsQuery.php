<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Queries\Android\Credentials;

/**
 * Query to set uploaded FCM credentials on existing Android app credentials.
 */
class SetFcmKeyOnAndroidAppCredentialsQuery
{
    /**
     * Build the query.
     * @param string $androidAppCredentialsId - The ID of the Android app credentials to update.
     * @param string $fcmKeyId - The ID of the FCM key to add.
     * @return array - The query.
     */
    public function build(
        string $androidAppCredentialsId,
        string $fcmKeyId
    ): array {
        return [
            "operationName" => "SetFcmKeyOnAndroidAppCredentials",
            "variables" => [
                "androidAppCredentialsId" => $androidAppCredentialsId,
                "fcmId" => $fcmKeyId
            ],
            "query" => '
                mutation SetFcmKeyOnAndroidAppCredentials($androidAppCredentialsId: ID!, $fcmId: ID!) {
                    androidAppCredentials {
                        setFcm(id: $androidAppCredentialsId, fcmId: $fcmId) {
                            id
                            __typename
                        }
                        __typename
                    }
                }'
        ];
    }
}
