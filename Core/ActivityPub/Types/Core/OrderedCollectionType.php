<?php
namespace Minds\Core\ActivityPub\Types\Core;

use Minds\Core\ActivityPub\Attributes\ExportProperty;

class OrderedCollectionType extends CollectionType
{
    #[ExportProperty]
    protected string $type = 'OreredCollection';

    /**
    * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-items
    */
    #[ExportProperty]
    protected array $orderedItems;

    public function setOrderedItems(array $items): self
    {
        $this->orderedItems = $items;
        return $this;
    }

}
