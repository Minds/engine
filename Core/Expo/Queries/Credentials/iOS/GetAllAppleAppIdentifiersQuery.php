<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Queries\Credentials\iOS;

/**
 * Query to get all Apple app identifiers for an account.
 */
class GetAllAppleAppIdentifiersQuery
{
    /**
     * Build the query.
     * @param string $accountName - The name of the account to get the Apple app identifiers for.
     * @return array - The query.
     */
    public function build(
        string $accountName,
    ): array {
        return [
            "operationName" => "GetAllAppleAppIdentifiers",
            "variables" => [
               "accountName" => $accountName
            ],
            "query" => '
                query GetAllAppleAppIdentifiers($accountName: String!) {
                    account {
                    byName(accountName: $accountName) {
                        id
                        appleAppIdentifiers {
                        ...AppleAppIdentifierData
                        __typename
                        }
                        __typename
                    }
                    __typename
                    }
                }
                
                fragment AppleAppIdentifierData on AppleAppIdentifier {
                    id
                    bundleIdentifier
                    appleTeam {
                    ...AppleTeamData
                    __typename
                    }
                    __typename
                }
                
                fragment AppleTeamData on AppleTeam {
                    id
                    appleTeamIdentifier
                    appleTeamName
                    __typename
                }
            '
        ];
    }
}
