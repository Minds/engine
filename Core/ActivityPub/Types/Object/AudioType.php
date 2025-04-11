<?php
namespace Minds\Core\ActivityPub\Types\Object;

use Minds\Core\ActivityPub\Attributes\ExportProperty;

class AudioType extends DocumentType
{
    #[ExportProperty]
    protected string $type = 'Audio';
}
