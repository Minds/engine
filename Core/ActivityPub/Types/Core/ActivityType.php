<?php
namespace Minds\Core\ActivityPub\Types\Core;

use Minds\Core\ActivityPub\Attributes\ExportProperty;
use Minds\Core\ActivityPub\Types\AbstractType;
use Minds\Core\ActivityPub\Types\Actor\PersonType;
use Minds\Entities\EntityInterface;
use Minds\Entities\ExportableInterface;

class ActivityType extends ObjectType
{
    #[ExportProperty]
    protected string $type = 'Activity';

    #[ExportProperty]
    public PersonType $actor;

    #[ExportProperty]
    public ObjectType $object;
}
