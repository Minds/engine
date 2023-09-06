<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases\Apple\Types;

use Lcobucci\JWT\UnencryptedToken;

class JWSTransactionInfo
{
    public function __construct(
        public readonly int $expiresDate, // millis
        public readonly int $originalPurchaseDate, // millis
        public readonly string $bundleId = "",
        public readonly string $environment = "",
        public readonly bool $isUpgraded = false,
        public readonly string $originalTransactionId = "",
        public readonly string $productId = "",

    ) {
    }

    public static function fromToken(UnencryptedToken $token): self
    {
        $payload = (object) base64_decode($token->payload());
        return new self(
            $payload->expiresDate,
            $payload->originalPurchaseDate,
            $payload->bundleId,
            $payload->environment,
            $payload->isUpgraded,
            $payload->originalTransactionId,
            $payload->productId
        );
    }
}
