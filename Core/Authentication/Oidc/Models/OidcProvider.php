<?php
namespace Minds\Core\Authentication\Oidc\Models;

class OidcProvider
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly string $issuer,
        public readonly string $clientId,
        public readonly string $clientSecretCipherText,
        public readonly array $configs,
    ) {
        
    }
}
