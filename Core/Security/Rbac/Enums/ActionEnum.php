<?php
namespace Minds\Core\Security\Rbac\Enums;

enum ActionEnum
{
    case CREATE;
    case READ;
    case UPDATE;
    case DELETE;
    case JOIN;
}
