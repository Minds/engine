<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Types;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class SiteMembership
{
    public function __construct(
        #[Field(outputType: "String!")] public readonly int      $membershipGuid,
        #[Field] public readonly string                          $membershipName,
        #[Field] public readonly string                          $membershipDescription,
        #[Field] public readonly int                             $membershipPriceInCents,
        #[Field] public readonly string                          $priceCurrency,
        #[Field] public readonly SiteMembershipBillingPeriodEnum $membershipBillingPeriod,
    )
    {
    }
}
