<?php
namespace Minds\Core\ActivityPub\Types\Activity;

use Minds\Core\ActivityPub\Attributes\ExportProperty;
use Minds\Core\ActivityPub\Types\Core\ActivityType;

/**
 * https://www.w3.org/TR/activitystreams-vocabulary/#dfn-announce
 */
class AnnounceType extends ActivityType
{
    #[ExportProperty]
    protected string $type = 'Announce';

}
