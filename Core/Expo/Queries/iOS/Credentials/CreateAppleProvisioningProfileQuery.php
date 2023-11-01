<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Queries\iOS\Credentials;

/**
 * Query to add an Apple provisioning profile to Expo.
 */
class CreateAppleProvisioningProfileQuery
{
    /**
     * Build the query.
     * @param string $appleProvisioningProfile - The provisioning profile to create.
     * @param string $appleAppIdentifierId - The ID of the Apple app identifier to use.
     * @param string $accountId - The ID of the account to use.
     * @return array - The query.
     */
    public function build(
        string $appleProvisioningProfile,
        string $appleAppIdentifierId,
        string $accountId
    ): array {
        return [
            "operationName" => "CreateAppleProvisioningProfile",
            "variables" => [
               "appleProvisioningProfileInput" => [
                  "appleProvisioningProfile" => $appleProvisioningProfile,
               ],
               "accountId" => $accountId,
               "appleAppIdentifierId" => $appleAppIdentifierId
            ],
            "query" => '
                mutation CreateAppleProvisioningProfile($appleProvisioningProfileInput: AppleProvisioningProfileInput!, $accountId: ID!, $appleAppIdentifierId: ID!) {
                    appleProvisioningProfile {
                        createAppleProvisioningProfile(
                            appleProvisioningProfileInput: $appleProvisioningProfileInput
                            accountId: $accountId
                            appleAppIdentifierId: $appleAppIdentifierId
                        ) {
                            ...AppleProvisioningProfileData
                            __typename
                        }
                        __typename
                    }
                }
                
                fragment AppleProvisioningProfileData on AppleProvisioningProfile {
                    __typename
                    id
                    appleTeam {
                        ...AppleTeamData
                        __typename
                    }
                    expiration
                    appleAppIdentifier {
                        ...AppleAppIdentifierData
                        __typename
                    }
                    appleUUID
                    developerPortalIdentifier
                    status
                    createdAt
                    updatedAt
                    provisioningProfile
                }
                
                fragment AppleTeamData on AppleTeam {
                    id
                    appleTeamIdentifier
                    appleTeamName
                    __typename
                }
                
                fragment AppleAppIdentifierData on AppleAppIdentifier {
                    id
                    bundleIdentifier
                    appleTeam {
                        ...AppleTeamData
                        __typename
                    }
                    __typename
                }'
         ] ;
    }
}
