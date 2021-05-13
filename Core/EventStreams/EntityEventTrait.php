<?php
namespace Minds\Core\EventStreams;

use Minds\Entities\User;

trait EntityEventTrait
{
    /** @var mixed */
    protected $entity;

    /** @var User */
    protected $user;

    /**
     * @param mixed $entity
     * @return self
     */
    public function setEntity($entity): self
    {
        $this->entity = $entity;
        return $this;
    }

    /**
     * @param User $user
     * @return self
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }
}
