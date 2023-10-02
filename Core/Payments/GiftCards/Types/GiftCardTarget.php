<?php
declare(strict_types=1);

namespace Minds\Core\Payments\GiftCards\Types;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Input;

#[Input]
class GiftCardTarget
{
    public function __construct(
        #[Field] public readonly ?string $targetUsername = null,
        #[Field(inputType: 'String')] public readonly ?int $targetUserGuid = null,
        #[Field] public readonly ?string $targetEmail = null,
    ) {
    }
}
