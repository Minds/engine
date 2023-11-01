<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Queries\iOS\Credentials;

/**
 * Query to add an Apple push key to Expo.
 */
class CreateApplePushKeyQuery
{
    /**
     * Build the query.
     * @param string $keyIdentifier - The key identifier.
     * @param string $keyP8 - The key in P8 format.
     * @param string $appleTeamId - The ID of the Apple team to use.
     * @param string $accountId - The ID of the account to use.
     * @return array - The query.
     */
    public function build(
        string $keyIdentifier,
        string $keyP8,
        string $appleTeamId,
        string $accountId
    ): array {
        return [
            "operationName" => "CreateApplePushKey",
            "variables" => [
                "applePushKeyInput" => [
                    "keyIdentifier" => $keyIdentifier,
                    "keyP8" => $keyP8,
                    "appleTeamId" => $appleTeamId
                ],
                "accountId" => $accountId
            ],
            "query" => '
                mutation CreateApplePushKey($applePushKeyInput: ApplePushKeyInput!, $accountId: ID!) {
                    applePushKey {
                        createApplePushKey(applePushKeyInput: $applePushKeyInput, accountId: $accountId) {
                            ...ApplePushKeyData
                            __typename
                        }
                        __typename
                    }
                }

                fragment ApplePushKeyData on ApplePushKey {
                    __typename
                    id
                    appleTeam {
                        ...AppleTeamData
                        __typename
                    }
                    keyIdentifier
                    createdAt
                    updatedAt
                    keyP8
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
