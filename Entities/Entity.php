<?php
namespace Minds\Entities;

/**
 * Base Entity
 * @todo Do not inherit from ElggEntity
 */
class Entity extends \ElggEntity
{
    private array $clientMeta = [];

    protected $exportContext = false;

    public function hasExportContext()
    {
        return $this->exportContext;
    }

    public function setExportContext($exportContext)
    {
        $this->exportContext = $exportContext;
        return $this;
    }

    public function setClientMeta(array $clientMeta): self
    {
        $this->clientMeta = $clientMeta;
        return $this;
    }

    public function getClientMeta(): array
    {
        return $this->clientMeta;
    }
}
