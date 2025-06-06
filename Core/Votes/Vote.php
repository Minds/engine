<?php
/**
 * Vote
 * @author Mark
 */
namespace Minds\Core\Votes;

use Minds\Entities\ExportableInterface;
use Minds\Entities\Factory;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;

class Vote implements ExportableInterface
{
    /** @var mixed */
    protected $entity;

    /** @var User */
    protected $actor;

    /** @var string */
    protected $direction;

    /**
     * Sets the entity of the vote
     * @param mixed $entity
     * @return $this
     * @throws NotFoundException
     */
    public function setEntity($entity)
    {
        $this->entity = is_object($entity) ? $entity : Factory::build($entity);

        if (!$this->entity || !$this->entity->guid) {
            throw new NotFoundException('Entity not found');
        }

        return $this;
    }

    /**
     * Returns the entity of the vote
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set the actor of the vote
     * @param User $actor
     * @return $this
     * @throws NotFoundException
     */
    public function setActor($actor)
    {
        $this->actor = $actor;

        if (!$this->actor || !$this->actor->guid) {
            throw new NotFoundException('Actor not found');
        }

        return $this;
    }

    /**
     * Returns the actor of the vote
     * @return User
     */
    public function getActor()
    {
        return $this->actor;
    }

    /**
     * Sets the direction of the vote
     * @param string $direction
     * @return $this
     * @throws \Exception
     */
    public function setDirection($direction)
    {
        if (!in_array($direction, [ 'up', 'down'], true)) {
            throw new \Exception('Invalid direction');
        }
        $this->direction = $direction;
        return $this;
    }

    /**
     * Returns the direction of the vote
     * @return string (up/down)
     */
    public function getDirection(bool $asEnum = false)
    {
        if ($asEnum) {
            return match ($this->direction) {
                'up' => Enums\VoteDirectionEnum::UP,
                'down' => Enums\VoteDirectionEnum::DOWN,
            };
        }
        return $this->direction;
    }

    /**
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        return [
            'entity_urn' => $this->getEntity()->getUrn(),
            'actor_guid' => $this->getActor()->getGuid(),
            'actor' => $this->getActor()->export(),
            'direction' => $this->getDirection(),
        ];
    }
}
