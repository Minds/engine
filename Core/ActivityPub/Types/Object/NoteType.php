<?php
namespace Minds\Core\ActivityPub\Types\Object;

use Minds\Core\ActivityPub\Attributes\ExportProperty;
use Minds\Core\ActivityPub\Types\Actor\PersonType;
use Minds\Core\ActivityPub\Types\Core\ObjectType;

class NoteType extends ObjectType
{
    #[ExportProperty]
    protected string $type = 'Note';

    public ?PersonType $actor = null;
}
