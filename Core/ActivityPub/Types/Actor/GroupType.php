<?php
namespace Minds\Core\ActivityPub\Types\Actor;

use Minds\Core\ActivityPub\Attributes\ExportProperty;

/**
 * https://www.w3.org/TR/activitystreams-vocabulary/#dfn-group
 */
class GroupType extends AbstractActorType
{
    #[ExportProperty]
    protected string $type = 'Group';
}
