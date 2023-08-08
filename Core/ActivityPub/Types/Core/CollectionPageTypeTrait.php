<?php
namespace Minds\Core\ActivityPub\Types\Core;

use Minds\Core\ActivityPub\Attributes\ExportProperty;

trait CollectionPageTypeTrait
{
    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-partOf
     */
    #[ExportProperty]
    protected string $partOf;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-next
     */
    #[ExportProperty]
    protected string $next;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-prev
     */
    #[ExportProperty]
    protected string $prev;

    public function setPartOf(string $partOf): self
    {
        $this->partOf = $partOf;
        return $this;
    }

    public function setNext(string $next): self
    {
        $this->next = $next;
        return $this;
    }

    public function setPrev(string $prev): self
    {
        $this->prev = $prev;
        return $this;
    }
}
