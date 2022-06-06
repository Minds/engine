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
}
