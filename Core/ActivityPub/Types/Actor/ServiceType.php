<?php
namespace Minds\Core\ActivityPub\Types\Actor;

use Minds\Core\ActivityPub\Attributes\ExportProperty;

/**
 * https://www.w3.org/TR/activitystreams-vocabulary/#dfn-service
 */
class ServiceType extends AbstractActorType
{
    #[ExportProperty]
    protected string $type = 'Service';
}
