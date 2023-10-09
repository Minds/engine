<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases\Apple\Types;

use Lcobucci\JWT\UnencryptedToken;
use Minds\Core\Payments\InAppPurchases\Apple\Enums\ApplePurchaseStatusEnum;

class AppleConsumablePurchase
{
    public function __construct(
        public readonly int $originalPurchaseDate, // millis
        public readonly string $bundleId = "",
        public readonly string $environment = "",
        public readonly string $originalTransactionId = "",
        public readonly string $productId = "",
        public readonly ApplePurchaseStatusEnum $purchaseState = ApplePurchaseStatusEnum::purchasing,
    ) {
    }

    public static function fromToken(UnencryptedToken $token): self
    {
        return new self(
            originalPurchaseDate: $token->claims()->get('originalPurchaseDate'),
            bundleId: $token->claims()->get('bundleId'),
            environment: $token->claims()->get('environment'),
            originalTransactionId: $token->claims()->get('originalTransactionId'),
            productId: $token->claims()->get('productId'),
            purchaseState: ApplePurchaseStatusEnum::purchased
        );
    }
}
