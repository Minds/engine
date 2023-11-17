<?php
declare(strict_types=1);

namespace Minds\Core\Reports\Enums;

use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
enum ReportActionEnum: int
{
    case IGNORE = 1;
    case DELETE = 2;
    case BAN = 3;
}
