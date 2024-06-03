<?php
namespace Minds\Core\Router\Enums;

enum ApiScopeEnum
{
    /** The router will allow endpoints if his scope is helf */
    case ALL;

    /** The route will allow write access to site memberships if this scope is held */
    case SITE_MEMBERSHIP_WRITE;

    /** The route will allow creation of a tenant (trial) - (Minds only) */
    case TENANT_CREATE_TRIAL;
}
