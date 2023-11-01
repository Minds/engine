<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Queries\Android\Credentials;

/**
 * Query to add a google service account to Expo.
 */
class CreateGoogleServiceAccountKeyQuery
{
    /**
     * Build the query.
     * @param string $accountId - The ID of the account to add the google service account to.
     * @param array $googleServiceAccountCredentials - The google service account credentials (Parsed JSON object).
     * @return array - The query.
     */
    public function build(
        string $accountId,
        array $googleServiceAccountCredentials
    ): array {
        return [
            "operationName" => "CreateGoogleServiceAccountKey",
            "variables" =>   [
                "googleServiceAccountKeyInput" => [
                    "jsonKey" => $googleServiceAccountCredentials
                ],
                "accountId" => $accountId
            ],
            "query" => '
                mutation CreateGoogleServiceAccountKey($accountId: ID!, $googleServiceAccountKeyInput: GoogleServiceAccountKeyInput!) {
                    googleServiceAccountKey {
                        createGoogleServiceAccountKey(
                            accountId: $accountId
                            googleServiceAccountKeyInput: $googleServiceAccountKeyInput
                        ) {
                            ...GoogleServiceAccountKeyData
                            __typename
                        }
                        __typename
                    }
                }
                
                fragment GoogleServiceAccountKeyData on GoogleServiceAccountKey {
                    id
                    account {
                        id
                        __typename
                    }
                    projectIdentifier
                    privateKeyIdentifier
                    clientEmail
                    clientIdentifier
                    createdAt
                    updatedAt
                    __typename
                }',
            ];
    }
}
