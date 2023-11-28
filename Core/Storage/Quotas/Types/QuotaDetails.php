<?php
declare(strict_types=1);

namespace Minds\Core\Storage\Quotas\Types;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class QuotaDetails
{
    public function __construct(
        #[Field] public int $sizeInBytes,
    ) {
    }
}
