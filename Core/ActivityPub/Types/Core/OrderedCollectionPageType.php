<?php
namespace Minds\Core\ActivityPub\Types\Core;

use Minds\Core\ActivityPub\Attributes\ExportProperty;

class OrderedCollectionPageType extends OrderedCollectionType
{
    use CollectionPageTypeTrait;

    #[ExportProperty]
    protected string $type = 'OrderedCollectionPage';
}
