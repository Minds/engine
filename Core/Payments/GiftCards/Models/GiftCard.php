<?php
namespace Minds\Core\Payments\GiftCards\Models;

use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;

class GiftCard
{
    public function __construct(
        public readonly int $guid,
        public readonly GiftCardProductIdEnum $productId,
        public readonly float $amount,
        public readonly int $issuedByGuid,
        public readonly int $issuedAt,
        public readonly string $claimCode,
        public readonly int $expiresAt,
        public ?int $claimedByGuid = null,
        public ?int $claimedAt = null
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
