<?php
namespace Minds\Core\ActivityPub\Types\Object;

use Minds\Core\ActivityPub\Attributes\ExportProperty;

class ImageType extends DocumentType
{
    #[ExportProperty]
    protected string $type = 'Image';
}
