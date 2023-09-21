<?php
namespace Minds\Core\ActivityPub\Types\Actor;

use Minds\Core\ActivityPub\Attributes\ExportProperty;

/**
 * https://www.w3.org/TR/activitystreams-vocabulary/#dfn-application
 */
class ApplicationType extends AbstractActorType
{
    #[ExportProperty]
    protected string $type = 'Application';
}
