<?php
namespace Minds\Core\Payments\GiftCards\Models;

use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type(name: "GiftCardNode")]
class GiftCard
{
    public function __construct(
        #[Field] public readonly int $guid,
        #[Field] public readonly GiftCardProductIdEnum $productId,
        #[Field] public readonly float $amount,
        #[Field] public readonly int $issuedByGuid,
        #[Field] public readonly int $issuedAt,
        public readonly string $claimCode,
        #[Field] public readonly int $expiresAt,
        #[Field] public ?int $claimedByGuid = null,
        #[Field] public ?int $claimedAt = null,
        public float $balance = 0.00,
    ) {
    }

    public function setClaimedByGuid(int $claimedByGuid): self
    {
        $this->claimedByGuid = $claimedByGuid;
        return $this;
    }

    public function setClaimedAt(int $claimedAt): self
    {
        $this->claimedAt = $claimedAt;
        return $this;
    }

    public function isClaimed(): bool
    {
        return !!$this->claimedByGuid;
    }
}
