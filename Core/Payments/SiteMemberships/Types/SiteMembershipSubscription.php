<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Types;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class SiteMembershipSubscription
{
    public function __construct(
        #[Field(outputType: "String!")] public readonly int $membershipGuid,
        #[Field] public readonly int                        $validFromTimestamp,
        #[Field] public readonly ?int                       $validToTimestamp = null
    ) {
    }
}
