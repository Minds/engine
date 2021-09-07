<?php

namespace Minds\Common\Repository;

use Minds\Entities\ExportableInterface;

class IterableEntity
{
    /** @var string */
    protected $pagingToken;

    /** @var ExportableInterface */
    protected $entity;

    /**
     * @param ExportableInterface $entity
     * @param string $pagingToken
     */
    public function __construct(ExportableInterface $entity, ?string $pagingToken)
    {
        $this->entity = $entity;
        $this->pagingToken = $pagingToken;
    }

    /**
     * @return EntityInterface
     */
    public function getEntity(): ExportableInterface
    {
        return $this->entity;
    }

    /**
     * @return string
     */
    public function getPagingToken(): ?string
    {
        return $this->pagingToken;
    }

    /**
     * @return array
     */
    public function export(): array
    {
        return $this->entity->export();
    }
}
