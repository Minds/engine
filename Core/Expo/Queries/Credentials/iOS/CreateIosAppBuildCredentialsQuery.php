<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Queries\Credentials\iOS;

/**
 * Query to create iOS app build credentials.
 */
class CreateIosAppBuildCredentialsQuery
{
    /**
     * Build the query.
     * @param string $iosDistributionType - The type of distribution to use e.g. 'DEVELOPMENT'.
     * @param string $distributionCertificateId - The ID of the distribution certificate to use.
     * @param string $provisioningProfileId - The ID of the provisioning profile to use.
     * @param string $iosAppCredentialsId - The ID of the iOS app credentials to use.
     * @return array - The query.
     */
    public function build(
        string $iosDistributionType,
        string $distributionCertificateId,
        string $provisioningProfileId,
        string $iosAppCredentialsId,
    ): array {
        return  [
            "operationName" => "CreateIosAppBuildCredentials",
            "variables" => [
               "iosAppBuildCredentialsInput" => [
                  "iosDistributionType" => $iosDistributionType,
                  "distributionCertificateId" => $distributionCertificateId,
                  "provisioningProfileId" => $provisioningProfileId
               ],
               "iosAppCredentialsId" => $iosAppCredentialsId
            ],
            "query" => '
                mutation CreateIosAppBuildCredentials($iosAppBuildCredentialsInput: IosAppBuildCredentialsInput!, $iosAppCredentialsId: ID!) {
                    iosAppBuildCredentials {
                        createIosAppBuildCredentials(
                            iosAppBuildCredentialsInput: $iosAppBuildCredentialsInput
                            iosAppCredentialsId: $iosAppCredentialsId
                        ) {
                            ...IosAppBuildCredentialsData
                            __typename
                        }
                        __typename
                    }
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
                }'
        ];
    }
}
