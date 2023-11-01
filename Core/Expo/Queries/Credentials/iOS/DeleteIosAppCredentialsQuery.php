<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Queries\Credentials\iOS;

/**
 * Query to delete iOS app credentials.
 */
class DeleteIosAppCredentialsQuery
{
    /**
     * Build the query.
     * @param string $iosAppCredentialsId - The ID of the iOS app credentials to delete.
     * @return array - The query.
     */
    public function build(
        string $iosAppCredentialsId,
    ): array {
        return [
            "operationName" => "DeleteIosAppCredentials",
            "variables" => [
                "id" => $iosAppCredentialsId
            ],
            "query" => '
                mutation DeleteIosAppCredentials($id: ID!) {
                    iosAppCredentials {
                        deleteIosAppCredentials(id: $id) {
                            id
                            __typename
                        }
                        __typename
                    }
                }'
        ];
    }
}
