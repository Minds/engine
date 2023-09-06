<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases\Models;

use Minds\Entities\User;

class InAppPurchase
{
    public function __construct(
        public string $source = "",
        public string $purchaseToken = "",
        public readonly string $subscriptionId = "",
        public readonly string $productId = "",
        public ?User $user = null,
        public ?int $expiresMillis = null
    ) {
    }

    /**
     * Set the user
     * @param User $user
     * @return self
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Set the expiry timestamp (millis)
     * @param int $expiresMillis
     * @return self
     */
    public function setExpiresMillis(int $expiresMillis): self
    {
        $this->expiresMillis = $expiresMillis;
        return $this;
    }
}
