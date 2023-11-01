<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Queries\Credentials\Android;

/**
 * Query to add an android keystore to Expo.
 */
class CreateAndroidKeystoreQuery
{
    /**
     * Build the query.
     * @param string $accountId - The ID of the account to add the keystore to.
     * @param string $androidKeystorePassword - The password for the keystore.
     * @param string $androidKeystoreKeyAlias - The alias for the keystore.
     * @param string $androidKeystoreKeyPassword - The password for the keystore key.
     * @param string $androidBase64EncodedKeystore - The base64 encoded keystore.
     * @return array - The query.
     */
    public function build(
        string $accountId,
        string $androidKeystorePassword,
        string $androidKeystoreKeyAlias,
        string $androidKeystoreKeyPassword,
        string $androidBase64EncodedKeystore
    ): array {
        return [
            "operationName" => 'CreateAndroidKeystore',
            "variables" => [
                "androidKeystoreInput" => [
                    "keystorePassword" => $androidKeystorePassword,
                    "keyAlias" => $androidKeystoreKeyAlias,
                    "keyPassword" => $androidKeystoreKeyPassword,
                    "base64EncodedKeystore" => $androidBase64EncodedKeystore
                ],
                "accountId" => $accountId
            ],
            "query" => '
                mutation CreateAndroidKeystore(
                    $androidKeystoreInput: AndroidKeystoreInput!
                    $accountId: ID!
                ) {
                    androidKeystore {
                        createAndroidKeystore(
                            androidKeystoreInput: $androidKeystoreInput
                            accountId: $accountId
                        ) {
                            ...AndroidKeystoreData
                            __typename
                        }
                        __typename
                    }
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
                }'
        ];
    }
}
