<?php
namespace Minds\Core\MultiTenant\CustomPages\Enums;

enum CustomPageTypesEnum: string
{
    // Start with 1 to avoid misinterpretations of 0 as falsy
    case PRIVACY_POLICY = 'privacy_policy';
    case TERMS_OF_SERVICE = 'terms';
    case COMMUNITY_GUIDELINES = 'community_guidelines';
}
