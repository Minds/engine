<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Queries\Credentials\iOS;

/**
 * Query to add an Apple app identifier to Expo.
 */
class CreateAppleAppIdentifierQuery
{
    /**
     * Build the query.
     * @param string $bundleIdentifier - The bundle identifier to use.
     * @param string $accountId - The ID of the account to use.
     * @return array - The query.
     */
    public function build(
        string $bundleIdentifier,
        string $accountId
    ): array {
        return [
            "operationName" => "CreateAppleAppIdentifier",
            "variables" => [
               "appleAppIdentifierInput" => [
                  "bundleIdentifier" => $bundleIdentifier
               ],
               "accountId" => $accountId
            ],
            "query" => '
                mutation CreateAppleAppIdentifier($appleAppIdentifierInput: AppleAppIdentifierInput!, $accountId: ID!) {
                    appleAppIdentifier {
                        createAppleAppIdentifier(
                            appleAppIdentifierInput: $appleAppIdentifierInput
                            accountId: $accountId
                        ) {
                            ...AppleAppIdentifierData
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
                }'
        ];
    }
}
