<?php
namespace Minds\Core\Search\Enums;

use TheCodingMachine\GraphQLite\Annotations\Input;

#[Input()]
enum SearchFilterEnum
{
    case TOP;
    case LATEST;
    case USER;
    case GROUP;
}
