<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Types;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class Customer
{
    public function __construct(
        /**
         * @var int $userGuid The Minds user guid
         */
        #[Field(outputType: "String!")] public readonly int $userGuid,
        #[Field] public readonly string $lagoCustomerId,
        #[Field] public readonly string $name,
        #[Field] public readonly int $createdAt,
        #[Field] public readonly ?BillingConfiguration $billingConfiguration = null,
        #[Field] public readonly ?int $updatedAt = null,
        #[Field] public readonly ?string $email = null,
    ) {
    }
}
