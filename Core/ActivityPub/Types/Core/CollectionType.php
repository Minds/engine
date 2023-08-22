<?php
namespace Minds\Core\ActivityPub\Types\Core;

use Minds\Core\ActivityPub\Attributes\ExportProperty;

class CollectionType extends ObjectType
{
    #[ExportProperty]
    protected string $type = 'Collection';

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-totalitems
     */
    #[ExportProperty]
    protected int $totalItems;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-current
     */
    #[ExportProperty]
    protected string $current;
    
    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-first
     */
    #[ExportProperty]
    protected string $first;
    
    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-last
     */
    #[ExportProperty]
    protected string $last;

    /**
    * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-items
    */
    #[ExportProperty]
    protected array $items;
}
