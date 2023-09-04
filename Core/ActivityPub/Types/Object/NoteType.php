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

    /**
     * Non-standard quote post field
     * TODO: Do proper schema validation for this property
     */
    public string $quoteUri;
}
