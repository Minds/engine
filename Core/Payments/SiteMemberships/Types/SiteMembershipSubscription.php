<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Types;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class SiteMembershipSubscription
{
    public function __construct(
        public readonly int $userGuid,
        #[Field] public readonly int $membershipSubscriptionId,
        #[Field(outputType: "String!")] public readonly int $membershipGuid,
        public readonly ?string $stripeSubscriptionId,
        #[Field] public readonly bool $autoRenew = true,
        #[Field] public readonly bool $isManual = false,
        #[Field] public readonly ?int $validFromTimestamp = null,
        #[Field] public readonly ?int $validToTimestamp = null
    ) {
    }
}
