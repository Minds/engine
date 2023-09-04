<?php
namespace Minds\Core\ActivityPub\Types\Object;

use Minds\Core\ActivityPub\Attributes\ExportProperty;
use Minds\Core\ActivityPub\Types\Core\ObjectType;

class DocumentType extends ObjectType
{
    #[ExportProperty]
    protected string $type = 'Document';
}
