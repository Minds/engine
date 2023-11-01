<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Queries\Credentials\iOS;

/**
 * Query to add an Apple distribution certificate to Expo.
 */
class CreateAppleDistributionCertificateQuery
{
    /**
     * Build the query.
     * @param string $certPassword - The password for the certificate.
     * @param string $certP12 - The certificate in p12 format.
     * @param string $accountId - The ID of the account to use.
     * @return array - The query.
     */
    public function build(
        string $certPassword,
        string $certP12,
        string $accountId
    ): array {
        return  [
            "operationName" => "CreateAppleDistributionCertificate",
            "variables" => [
               "appleDistributionCertificateInput" => [
                  "certPassword" => $certPassword,
                  "certP12" => $certP12
               ],
               "accountId" => $accountId
            ],
            "query" => '
                mutation CreateAppleDistributionCertificate($appleDistributionCertificateInput: AppleDistributionCertificateInput!, $accountId: ID!) {
                    appleDistributionCertificate {
                        createAppleDistributionCertificate(
                            appleDistributionCertificateInput: $appleDistributionCertificateInput
                            accountId: $accountId
                        ) {
                            ...AppleDistributionCertificateData
                            __typename
                        }
                        __typename
                    }
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
                
                fragment AppleTeamData on AppleTeam {
                    id
                    appleTeamIdentifier
                    appleTeamName
                    __typename
                }'
         ] ;
    }
}
