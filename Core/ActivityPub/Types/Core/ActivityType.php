<?php
namespace Minds\Core\ActivityPub\Types\Core;

use Minds\Core\ActivityPub\Attributes\ExportProperty;
use Minds\Core\ActivityPub\Helpers\JsonLdHelper;
use Minds\Core\ActivityPub\Types\Actor\PersonType;

class ActivityType extends ObjectType
{
    #[ExportProperty]
    protected string $type = 'Activity';

    #[ExportProperty]
    public PersonType|string $actor;

    #[ExportProperty]
    public ObjectType|string $object;

    public function export(array $extras = []): array
    {
        $exported = parent::export($extras);
        $exported['actor'] = JsonLdHelper::getValueOrId($this->actor);
        return $exported;
    }

}
