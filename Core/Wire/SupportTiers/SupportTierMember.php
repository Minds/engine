<?php
namespace Minds\Core\Wire\SupportTiers;

use Minds\Traits\MagicAttributes;

class SupportTierMember
{
    use MagicAttributes;

    /** @var SupportTier */
    protected $supportTier;

    /** @var User */
    protected $user;

    /** @var Subscription */
    protected $subscription;

    /**
     * Export
     * @param array $extras
     * @return array
     */
    public function export($extras = []): array
    {
        return [
            'support_tier' => $this->supportTier->export(),
            'user' => $this->user->export(),
            'subscription' => $this->subscription->export(),
        ];
    }
}
