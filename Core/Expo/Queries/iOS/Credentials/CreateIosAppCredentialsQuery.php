<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Queries\iOS\Credentials;

/**
 * Query to create iOS app credentials.
 */
class CreateIosAppCredentialsQuery
{
    /**
     * Build the query.
     * @param string $appleAppIdentifierId - The ID of the Apple app identifier to use.
     * @param string $appId - The ID of the app to use.
     * @param string|null $pushKeyId - The ID of the push key to use (optional - can be set later).
     * @param string|null $ascKeyId - The ID of the ASC key to use (optional - can be set later).
     * @return array - The query.
     */
    public function build(
        string $appleAppIdentifierId,
        string $appId,
        ?string $pushKeyId,
        ?string $ascKeyId
    ): array {
        $iosAppCredentialsInput = [];

        if ($pushKeyId) {
            $iosAppCredentialsInput['pushKeyId'] = $pushKeyId;
        }

        if ($ascKeyId) {
            $iosAppCredentialsInput['appStoreConnectApiKeyForSubmissionsId'] = $ascKeyId;
        }

        return [
            "operationName" => "CreateIosAppCredentials",
            "variables" => [
                "iosAppCredentialsInput" => $iosAppCredentialsInput,
                "appleAppIdentifierId" => $appleAppIdentifierId,
                "appId" => $appId
            ],
            "query" => '
                mutation CreateIosAppCredentials($iosAppCredentialsInput: IosAppCredentialsInput!, $appId: ID!, $appleAppIdentifierId: ID!) {
                    iosAppCredentials {
                        createIosAppCredentials(
                            iosAppCredentialsInput: $iosAppCredentialsInput
                            appId: $appId
                            appleAppIdentifierId: $appleAppIdentifierId
                        ) {
                            ...IosAppCredentialsData
                            __typename
                        }
                        __typename
                    }
                }
                
                fragment IosAppCredentialsData on IosAppCredentials {
                    id
                    app {
                        id
                        __typename
                    }
                    appleTeam {
                        ...AppleTeamData
                        __typename
                    }
                    appleAppIdentifier {
                        ...AppleAppIdentifierData
                        __typename
                    }
                    pushKey {
                        ...ApplePushKeyData
                        __typename
                    }
                    iosAppBuildCredentialsList {
                        ...IosAppBuildCredentialsData
                        __typename
                    }
                    appStoreConnectApiKeyForSubmissions {
                        ...AppStoreConnectApiKeyData
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
                
                fragment AppleAppIdentifierData on AppleAppIdentifier {
                    id
                    bundleIdentifier
                    appleTeam {
                        ...AppleTeamData
                        __typename
                    }
                    __typename
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
                
                fragment IosAppBuildCredentialsData on IosAppBuildCredentials {
                    id
                    provisioningProfile {
                        ...AppleProvisioningProfileData
                        __typename
                    }
                    iosDistributionType
                    distributionCertificate {
                        ...AppleDistributionCertificateData
                        __typename
                    }
                    __typename
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
                
                fragment AppleDistributionCertificateData on AppleDistributionCertificate {
                    __typename
                    id
                    appleTeam {
                        ...AppleTeamData
                        __typename
                    }
                    serialNumber
                    validityNotBefore
                    validityNotAfter
                    developerPortalIdentifier
                    createdAt
                    updatedAt
                    certificateP12
                    certificatePassword
                    certificatePrivateSigningKey
                }
                
                fragment AppStoreConnectApiKeyData on AppStoreConnectApiKey {
                    id
                    appleTeam {
                        ...AppleTeamData
                        __typename
                    }
                    issuerIdentifier
                    keyIdentifier
                    name
                    roles
                    createdAt
                    updatedAt
                    __typename
                }'
        ];
    }
}
