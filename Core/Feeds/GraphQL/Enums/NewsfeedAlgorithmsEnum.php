<?php
namespace Minds\Core\Feeds\GraphQL\Enums;

enum NewsfeedAlgorithmsEnum: string
{
    case LATEST = 'latest';
    case TOP = 'top';
    case FORYOU = 'for-you';
    case GROUPS = 'groups';
}
