<?php

namespace Minds\Core\Reports\Enums\Reasons\Illegal;

enum SubReasonEnum: int
{
    case TERRORISM = 1;
    case MINORS_SEXUALIZATION = 2;
    case EXTORTION = 3;
    case FRAUD = 4;
    case REVENGE_PORN = 5;
    case TRAFFICKING = 6;
    case ANIMAL_ABUSE = 7;
}
