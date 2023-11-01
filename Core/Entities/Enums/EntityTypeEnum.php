<?php
namespace Minds\Core\Entities\Enums;

enum EntityTypeEnum: string
{
    case USER = 'user';
    case ACTIVITY = 'activity';
    case OBJECT = 'object';
    case GROUP = 'group';
}
