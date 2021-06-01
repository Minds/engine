<?php
namespace Minds\Core\EventStreams;

use Minds\Entities\EntityInterface;
use Minds\Entities\User;

interface EntityEventInterface
{
    /**
     * @param EntityInterface $entity
     * @return self
     */
    public function setEntity(EntityInterface $entity): self;

    /*
     * @return mixed
     */
    public function getEntity();

    /**
     * @param User $user
     * @return self
     */
    public function setUser(User $user): self;

    /**
     * @return User
     */
    public function getUser(): User;
}
