<?php
namespace Minds\Core\ActivityPub\Types\Object;

use Minds\Core\ActivityPub\Attributes\ExportProperty;
use Minds\Core\ActivityPub\Types\Core\ObjectType;
use Minds\Entities\Activity;

class NoteType extends ObjectType
{
    #[ExportProperty]
    protected string $type = 'Note';
}
