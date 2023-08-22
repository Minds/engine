<?php
namespace Minds\Core\ActivityPub\Types\Activity;

use Minds\Core\ActivityPub\Attributes\ExportProperty;
use Minds\Core\ActivityPub\Types\Core\ActivityType;

/**
 * https://www.w3.org/TR/activitystreams-vocabulary/#dfn-follow
 */
class FollowType extends ActivityType
{
    #[ExportProperty]
    protected string $type = 'Follow';

}
