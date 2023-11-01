<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Queries\Android\Credentials;

/**
 * Query to delete Android app credentials.
 */
class DeleteAndroidAppCredentialsQuery
{
    /**
     * Build the query.
     * @param string $androidAppCredentialsId - The ID of the Android app credentials to delete.
     * @return array - The query.
     */
    public function build(
        string $androidAppCredentialsId,
    ): array {
        return [
            "operationName" => "DeleteAndroidAppCredentials",
            "variables" => [
               "id" => $androidAppCredentialsId
            ],
            "query" => '
                mutation DeleteAndroidAppCredentials($id: ID!) {
                    androidAppCredentials {
                        deleteAndroidAppCredentials(id: $id) {
                            id
                            __typename
                        }
                        __typename
                    }
                }'
        ];
    }
}
