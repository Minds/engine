<?php
namespace Minds\Core\Feeds\HideEntities;

class HideEntity
{
    public function __construct(
        protected string $userGuid,
        protected string $entityGuid,
    ) {
    }

    /**
     * @return string
     */
    public function getUserGuid(): string
    {
        return $this->userGuid;
    }

    /**
     * @return string
     */
    public function getEntityGuid(): string
    {
        return $this->entityGuid;
    }
}
