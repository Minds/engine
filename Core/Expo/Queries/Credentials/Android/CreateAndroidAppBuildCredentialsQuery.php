<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Queries\Credentials\Android;

/**
 * Query to create Android app build credentials.
 */
class CreateAndroidAppBuildCredentialsQuery
{
    /**
     * Build the query.
     * @param string $androidAppCredentialsId - The ID of the Android app credentials to update.
     * @param string $keystoreId - The ID of the keystore to add.
     * @param string $name - The name of the build credentials.
     * @return array - The query.
     */
    public function build(
        string $androidAppCredentialsId,
        string $keystoreId,
        string $name
    ): array {
        return [
            "operationName" => "CreateAndroidAppBuildCredentials",
            "variables" =>  [
                "androidAppBuildCredentialsInput" => [
                    "isDefault" => false,
                    "name" => $name,
                    "keystoreId" => $keystoreId
                ],
                "androidAppCredentialsId" => $androidAppCredentialsId
            ],
            "query" => '
                mutation CreateAndroidAppBuildCredentials($androidAppBuildCredentialsInput: AndroidAppBuildCredentialsInput!, $androidAppCredentialsId: ID!) {
                    androidAppBuildCredentials {
                        createAndroidAppBuildCredentials(
                            androidAppBuildCredentialsInput: $androidAppBuildCredentialsInput
                            androidAppCredentialsId: $androidAppCredentialsId
                        ) {
                            ...AndroidAppBuildCredentialsData
                            __typename
                        }
                        __typename
                    }
                }
                
                fragment AndroidAppBuildCredentialsData on AndroidAppBuildCredentials {
                    id
                    name
                    isDefault
                    isLegacy
                    androidKeystore {
                        ...AndroidKeystoreData
                        __typename
                    }
                    __typename
                }
                
                fragment AndroidKeystoreData on AndroidKeystore {
                    id
                    type
                    keyAlias
                    md5CertificateFingerprint
                    sha1CertificateFingerprint
                    sha256CertificateFingerprint
                    createdAt
                    updatedAt
                    keystore
                    keystorePassword
                    keyAlias
                    keyPassword
                    __typename
                }',
            ];
    }
}
