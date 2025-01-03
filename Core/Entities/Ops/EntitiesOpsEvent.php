<?php
namespace Minds\Core\Entities\Ops;

use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\TimebasedEventTrait;
use Minds\Exceptions\ServerErrorException;

class EntitiesOpsEvent implements EventInterface
{
    use TimebasedEventTrait;

    const OP_CREATE = 'create';
    const OP_UPDATE = 'update';
    const OP_DELETE = 'delete';

    protected string $entityUrn;
    protected string $op;
    protected int $timestamp = 0;
    protected string $entitySerialized;
    protected int $delaySecs = 0;

    /**
     * Set the entity urn that is being modified
     * @param string $entityUrn
     * @return self
     */
    public function setEntityUrn(string $entityUrn): self
    {
        $this->entityUrn = $entityUrn;
        return $this;
    }

    /**
     * @return string
     */
    public function getEntityUrn(): string
    {
        return $this->entityUrn;
    }

    /**
     * Set the op type and validated
     * @param string $entityUrn
     * @return self
     */
    public function setOp(string $op): self
    {
        if (!in_array($op, [ self::OP_CREATE, self::OP_UPDATE, self::OP_DELETE], true)) {
            throw new ServerErrorException("Invalid op provided");
        }
        $this->op = $op;
        return $this;
    }

    /**
     * @return string
     */
    public function getOp(): string
    {
        return $this->op;
    }

    /**
     * Set an array of data for entity. This is used for delete events to maintain
     * a copy of the source entity
     * @param array $json
     * @return self
     */
    public function setEntitySerialized(string $serialized): self
    {
        $this->entitySerialized = $serialized;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEntitySerialized(): ?string
    {
        return isset($this->entitySerialized) ? $this->entitySerialized : null;
    }

    /**
     * Sets the seconds to delay the message by
     */
    public function setDelaySecs(int $secs): self
    {
        $this->delaySecs = $secs;
        return $this;
    }

    /**
     * The number of seconds to delay the delivery by
     */
    public function getDelaySecs(): int
    {
        return $this->delaySecs;
    }
}
