<?php
namespace Minds\Core\Security\CorruptionFilter;

use Minds\Entities\Entity;
use Minds\Entities\User;

class Manager
{
    /**
     * determines whether an entity is corrupted
     * @param mixed $entity
     * @return bool
     */
    public function isCorrupted(mixed $entity): bool
    {
        switch ($entity->getType()) {
            case 'user':
                if (empty($entity->get('username'))) {
                    return true;
                };
                break;
        }

        return false;
    }
}
