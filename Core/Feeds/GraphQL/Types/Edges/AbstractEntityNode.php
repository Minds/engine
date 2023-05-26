<?php
namespace Minds\Core\Feeds\GraphQL\Types\Edges;

use Minds\Core\GraphQL\Types\NodeInterface;
use Minds\Core\Session;
use Minds\Entities\EntityInterface;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Abstract wrapper for entities to expand
 */
#[Type(name:"EntityNode")]
abstract class AbstractEntityNode implements NodeInterface
{
    public function __construct(
        protected EntityInterface $entity,
        protected ?User $loggedInUser = null
    ) {
        $this->loggedInUser ??= Session::getLoggedinUser();
    }

    #[Field]
    public function getId(): ID
    {
        return new ID($this->entity->getType() . '-'. $this->entity->getGuid());
    }

    #[Field]
    public function getGuid(): string
    {
        return $this->entity->getGuid();
    }

    #[Field]
    public function getUrn(): string
    {
        return $this->entity->getUrn();
    }

    /**
     * @return int[]
     */
    #[Field]
    public function getNsfw(): array
    {
        return method_exists($this->entity, 'getNsfw') ? $this->entity->getNsfw() : [];
    }

    /**
     * @return int[]
     */
    #[Field]
    public function getNsfwLock(): array
    {
        return method_exists($this->entity, 'getNsfwLock') ? $this->entity->getNsfwLock() : [];
    }

    #[Field(description: 'Unix timestamp representation of time created')]
    public function getTimeCreated(): int
    {
        return $this->entity->time_created;
    }

    #[Field(description: 'ISO 8601 timestamp representation of time created')]
    public function getTimeCreatedISO8601(): string
    {
        return date('c', $this->getTimeCreated());
    }
}
