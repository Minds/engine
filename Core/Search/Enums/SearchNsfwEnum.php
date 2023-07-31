<?php
namespace Minds\Core\Search\Enums;

use TheCodingMachine\GraphQLite\Annotations\Input;

#[Input()]
enum SearchNsfwEnum: int
{
    case NUDITY = 1;
    case PORNOGRAPHY = 2;
    case PROFANITY = 3;
    case VIOLENCE = 4;
    case RACE_RELIGION = 5;
    case OTHER = 6;
}
