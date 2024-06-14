<?php
namespace Minds\Core\Payments\SiteMemberships\Enums;

enum SiteMembershipBatchIdTypeEnum
{
    case GUID;
    case OIDC;
    case EMAIL;
}
