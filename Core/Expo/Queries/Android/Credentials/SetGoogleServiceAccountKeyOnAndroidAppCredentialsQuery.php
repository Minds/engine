<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Queries\Android\Credentials;

/**
 * Query to set uploaded google service account credentials on existing Android app credentials.
 */
class SetGoogleServiceAccountKeyOnAndroidAppCredentialsQuery
{
    /**
     * Build the query.
     * @param string $androidAppCredentialsId - The ID of the Android app credentials to update.
     * @param string $googleServiceAccountKeyId - The ID of the google service account key to add.
     * @return array - The query.
     */
    public function build(
        string $androidAppCredentialsId,
        string $googleServiceAccountKeyId
    ): array {
        return [
            "operationName" => "SetGoogleServiceAccountKeyOnAndroidAppCredentials",
            "variables" => [
                "androidAppCredentialsId" => $androidAppCredentialsId,
                "googleServiceAccountKeyId" => $googleServiceAccountKeyId
            ],
            "query" => '
                mutation SetGoogleServiceAccountKeyOnAndroidAppCredentials($androidAppCredentialsId: ID!, $googleServiceAccountKeyId: ID!) {
                    androidAppCredentials {
                        setGoogleServiceAccountKeyForSubmissions(
                            googleServiceAccountKeyId: $googleServiceAccountKeyId
                            id: $androidAppCredentialsId
                        ) {
                            id
                            __typename
                        }
                        __typename
                    }
                }
            '
        ];
    }
}
