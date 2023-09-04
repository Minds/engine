<?php
namespace Minds\Core\ActivityPub\Types\Core;

use Minds\Core\ActivityPub\Attributes\ExportProperty;

class CollectionPageType extends CollectionType
{
    use CollectionPageTypeTrait;

    #[ExportProperty]
    protected string $type = 'CollectionPage';
}
