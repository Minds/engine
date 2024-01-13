<?php
namespace Minds\Core\MultiTenant\CustomPages\Enums;

enum CustomPageTypesEnum: int
{
    // Start with 1 to avoid misinterpretations of 0 as falsy
    case PRIVACY_POLICY = 1;
    case TERMS_OF_SERVICE = 2;
    case COMMUNITY_GUIDELINES = 3;
}
