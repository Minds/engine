<?php
declare(strict_types=1);

namespace Minds\Core\Reports\Enums;

use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
enum ReportStatusEnum: int
{
    case PENDING = 1;
    case ACTIONED = 2;
}
