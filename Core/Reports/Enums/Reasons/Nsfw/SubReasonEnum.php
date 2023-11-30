<?php

namespace Minds\Core\Reports\Enums\Reasons\Nsfw;

use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type(name: 'NsfwSubReasonEnum')]
enum SubReasonEnum: int
{
    case NUDITY = 1;
    case PORNOGRAPHY = 2;
    case PROFANITY = 3;
    case VIOLENCE_GORE = 4;
    case RACE_RELIGION_GENDER = 5;
}
