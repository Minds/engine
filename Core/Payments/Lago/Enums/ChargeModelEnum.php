<?php

namespace Minds\Core\Payments\Lago\Enums;

enum ChargeModelEnum: string
{
    case STANDARD = "standard";
    case GRADUATED = "graduated";
    case GRADUATED_PERCENTAGE = "graduated_percentage";
    case PACKAGE = "package";
    case PERCENTAGE = "percentage";
    case VOLUME = "volume";
}
