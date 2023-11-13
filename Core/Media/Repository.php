<?php
namespace Minds\Core\Media;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Delete;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security;
use Minds\Entities;

class Repository
{
    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Delete $deleteAction = null,
    )
    {
        $this->entitiesBuilder ??= Di::_()->get(EntitiesBuilder::class);
        $this->deleteAction ??= new Delete();
    }

    public function getEntity($guid)
    {
        if (!$guid) {
            return false;
        }

        $entity = $this->entitiesBuilder->single($guid);

        if (!$entity || !$entity->guid || !Security\ACL::_()->read($entity)) {
            return false;
        }

        return $entity;
    }

    public function delete($guid)
    {
        if (!$guid) {
            return false;
        }

        $entity = $this->entitiesBuilder->single($guid);

        if (!$entity || !$entity->guid || !$entity->canEdit()) {
            return false;
        }

        $this->deleteAction->setEntity($entity)->delete();

        return true;
    }
}
