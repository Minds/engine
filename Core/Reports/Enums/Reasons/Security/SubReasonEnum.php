<?php

namespace Minds\Core\Reports\Enums\Reasons\Security;

use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type(name: 'SecuritySubReasonEnum')]
enum SubReasonEnum: int
{
    case HACKED_ACCOUNT = 1;
}
