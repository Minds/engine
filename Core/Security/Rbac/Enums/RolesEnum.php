<?php
namespace Minds\Core\Security\Rbac\Enums;

enum RolesEnum: int
{
    case OWNER = 0;
    case ADMIN = 1;
    case MODERATOR = 2;
    case VERIFIED = 3;
    case DEFAULT = 4;
    case PLUS = 5;
    case PRO = 6;
}

