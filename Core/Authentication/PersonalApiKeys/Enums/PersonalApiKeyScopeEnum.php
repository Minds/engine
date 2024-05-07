<?php
namespace Minds\Core\Authentication\PersonalApiKeys\Enums;

enum PersonalApiKeyScopeEnum
{
    /** The api key will be able to access ALL scopes */
    case ALL;

    /** The api key will be able to read and write site memberships */
    case SITE_MEMBERSHIP_WRITE;
}
