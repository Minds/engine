<?php
namespace Minds\Core\Payments\SiteMemberships\Repositories\DTO;

use DateTimeImmutable;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Entities\User;

class SiteMembershipSubscriptionDTO
{
    public function __construct(
        public readonly User $user,
        public readonly SiteMembership $siteMembership,
        public readonly ?string $stripeSubscriptionId = null,
        public readonly bool $isManual = false,
        public readonly ?DateTimeImmutable $validFrom = null,
        public readonly ?DateTimeImmutable $validTo = null,
    ) {
        
    }
}
